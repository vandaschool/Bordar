<?php

declare(strict_types=1);

namespace App\Features\Mentor\Services;

use App\Core\AuditLog;
use App\Core\Notifier;
use App\Features\Auth\Models\User;
use App\Features\Company\Models\Company;
use App\Features\Mentor\Models\Mentor;
use App\Features\Mentor\Models\MentorSession;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use PDOException;

final class MentorService
{
    private const DEFAULT_LOOKAHEAD_DAYS = 14;

    /** @param array<int, array{day: int, start: string, end: string}> $availability @param array<int, string> $expertise */
    public function saveProfile(string $userId, string $bio, array $expertise, array $availability, int $sessionLengthMinutes): array
    {
        $existing = Mentor::findByUserId($userId);
        $payload = [
            'bio' => $bio,
            'expertise' => json_encode(array_values($expertise), JSON_UNESCAPED_UNICODE),
            'availability' => json_encode($this->normalizeAvailability($availability), JSON_UNESCAPED_UNICODE),
            'session_length_minutes' => max(15, min(180, $sessionLengthMinutes)),
        ];

        if ($existing === null) {
            $id = Mentor::insert(array_merge($payload, ['user_id' => $userId]));
        } else {
            $id = $existing['id'];
            Mentor::update($id, $payload);
        }

        AuditLog::record('mentor.profile_updated', 'Mentor', $id, null, ['availability_rules' => count($availability)]);

        return Mentor::find($id);
    }

    /** @param array<int, array{day: mixed, start: mixed, end: mixed}> $availability */
    private function normalizeAvailability(array $availability): array
    {
        $normalized = [];

        foreach ($availability as $rule) {
            $day = (int) ($rule['day'] ?? -1);
            $start = (string) ($rule['start'] ?? '');
            $end = (string) ($rule['end'] ?? '');

            if ($day < 0 || $day > 6 || !preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end) || $start >= $end) {
                continue;
            }

            $normalized[] = ['day' => $day, 'start' => $start, 'end' => $end];
        }

        return $normalized;
    }

    /**
     * @return array<int, array{start_utc: string, end_utc: string}> Concrete bookable
     * slots over the next N days, already excluding past times and slots that
     * collide with an existing active booking.
     */
    public function availableSlots(array $mentor, int $daysAhead = self::DEFAULT_LOOKAHEAD_DAYS): array
    {
        $mentorUser = User::find($mentor['user_id']);
        $mentorTz = new DateTimeZone($mentorUser['timezone'] ?? 'Asia/Tehran');
        $availability = json_decode($mentor['availability'] ?? '[]', true) ?: [];
        $length = (int) $mentor['session_length_minutes'];

        if ($availability === [] || $length <= 0) {
            return [];
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $rangeStartUtc = $now->format('Y-m-d H:i:s');
        $rangeEndUtc = $now->modify("+{$daysAhead} days")->format('Y-m-d H:i:s');

        $booked = [];
        foreach (MentorSession::activeForMentorInRange($mentor['id'], $rangeStartUtc, $rangeEndUtc) as $session) {
            $booked[$session['start_time']] = true;
        }

        $slots = [];
        $interval = new DateInterval("PT{$length}M");

        for ($offset = 0; $offset < $daysAhead; $offset++) {
            $localDay = (new DateTimeImmutable('now', $mentorTz))->modify("+{$offset} days");
            $dayOfWeek = (int) $localDay->format('w');

            foreach ($availability as $rule) {
                if ($rule['day'] !== $dayOfWeek) {
                    continue;
                }

                $windowStart = DateTimeImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $localDay->format('Y-m-d') . ' ' . $rule['start'],
                    $mentorTz
                );
                $windowEnd = DateTimeImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $localDay->format('Y-m-d') . ' ' . $rule['end'],
                    $mentorTz
                );

                $slotStart = $windowStart;

                while (true) {
                    $slotEnd = $slotStart->add($interval);
                    if ($slotEnd > $windowEnd) {
                        break;
                    }

                    $startUtc = $slotStart->setTimezone(new DateTimeZone('UTC'));
                    $endUtc = $slotEnd->setTimezone(new DateTimeZone('UTC'));

                    if ($startUtc > $now && !isset($booked[$startUtc->format('Y-m-d H:i:s')])) {
                        $slots[] = ['start_utc' => $startUtc->format('Y-m-d H:i:s'), 'end_utc' => $endUtc->format('Y-m-d H:i:s')];
                    }

                    $slotStart = $slotEnd;
                }
            }
        }

        usort($slots, static fn ($a, $b) => $a['start_utc'] <=> $b['start_utc']);

        return $slots;
    }

    /**
     * Books a slot atomically: relies on the mentor_sessions.slot_lock_key
     * unique index to make concurrent double-booking of the same mentor+time
     * impossible even under simultaneous requests, rather than trusting an
     * application-level "is it free" check (which has a race window between
     * the SELECT and the INSERT).
     */
    public function bookSession(string $mentorId, string $companyId, string $startUtc, string $endUtc, string $agenda): array
    {
        if ($startUtc <= gmdate('Y-m-d H:i:s')) {
            throw new \InvalidArgumentException('این بازه زمانی دیگر در دسترس نیست.');
        }

        try {
            $id = MentorSession::insert([
                'mentor_id' => $mentorId,
                'company_id' => $companyId,
                'start_time' => $startUtc,
                'end_time' => $endUtc,
                'status' => 'SCHEDULED',
                'agenda' => $agenda,
            ]);
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000 || str_contains($e->getMessage(), 'uniq_mentor_slot_lock')) {
                throw new \RuntimeException('این بازه زمانی توسط شخص دیگری رزرو شد. لطفاً بازه دیگری انتخاب کنید.');
            }

            throw $e;
        }

        $session = MentorSession::find($id);
        AuditLog::record('mentor.session_booked', 'MentorSession', $id, null, ['mentor_id' => $mentorId, 'start_time' => $startUtc]);

        $this->notifyMentorOfBooking($mentorId, $companyId, $startUtc);

        return $session;
    }

    private function notifyMentorOfBooking(string $mentorId, string $companyId, string $startUtc): void
    {
        $mentor = Mentor::find($mentorId);
        $company = Company::find($companyId);

        if ($mentor === null) {
            return;
        }

        Notifier::send(
            'mentor.session_booked',
            $mentor['user_id'],
            'رزرو جلسه منتورینگ جدید',
            'شرکت «' . ($company['name'] ?? '') . '» یک جلسه منتورینگ جدید رزرو کرد.',
            '/mentor/sessions'
        );
    }

    public function cancelSession(string $sessionId, string $mentorUserId, string $actorNote = ''): array
    {
        $session = MentorSession::find($sessionId);

        if ($session === null) {
            throw new \RuntimeException('جلسه یافت نشد.');
        }

        $mentor = Mentor::find($session['mentor_id']);

        if ($mentor === null || $mentor['user_id'] !== $mentorUserId) {
            throw new \RuntimeException('شما اجازه لغو این جلسه را ندارید.');
        }

        MentorSession::update($sessionId, ['status' => 'CANCELED']);
        AuditLog::record('mentor.session_canceled', 'MentorSession', $sessionId, null, ['note' => $actorNote]);

        return MentorSession::find($sessionId);
    }

    public function recordOutcome(string $sessionId, string $mentorUserId, string $summary, string $actionPlan): array
    {
        $session = MentorSession::find($sessionId);

        if ($session === null) {
            throw new \RuntimeException('جلسه یافت نشد.');
        }

        $mentor = Mentor::find($session['mentor_id']);

        if ($mentor === null || $mentor['user_id'] !== $mentorUserId) {
            throw new \RuntimeException('شما اجازه ثبت خلاصه این جلسه را ندارید.');
        }

        MentorSession::update($sessionId, [
            'summary' => $summary,
            'action_plan' => $actionPlan,
            'status' => 'COMPLETED',
        ]);

        AuditLog::record('mentor.session_completed', 'MentorSession', $sessionId);

        $company = Company::find($session['company_id']);
        if ($company !== null) {
            Notifier::send(
                'mentor.session_completed',
                $company['owner_user_id'],
                'خلاصه جلسه منتورینگ ثبت شد',
                'خلاصه و برنامه اقدام جلسه منتورینگ شما آماده است.',
                '/mentor/my-sessions'
            );
        }

        return MentorSession::find($sessionId);
    }
}
