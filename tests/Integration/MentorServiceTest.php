<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Core\Model;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Company\Models\Company;
use App\Features\Mentor\Models\Mentor;
use App\Features\Mentor\Models\MentorSession;
use App\Features\Mentor\Services\MentorService;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;

final class MentorServiceTest extends TestCase
{
    private static PDO $pdo;

    private static string $applicantRoleId;

    private static string $mentorRoleId;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();

        foreach (['Applicant', 'Mentor'] as $roleName) {
            $role = Role::findByName($roleName);
            $id = $role['id'] ?? Model::uuid();
            if ($role === null) {
                Role::insert(['id' => $id, 'name' => $roleName, 'description' => $roleName, 'permissions' => '[]']);
            }
            if ($roleName === 'Applicant') {
                self::$applicantRoleId = $id;
            } else {
                self::$mentorRoleId = $id;
            }
        }
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['mentor_sessions', 'mentors', 'companies', 'users'] as $table) {
            self::$pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function makeMentor(string $suffix, string $timezone = 'Asia/Tehran'): array
    {
        $userId = User::insert([
            'email' => "mentor-{$suffix}@example.com",
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'M', 'last_name' => $suffix,
            'role_id' => self::$mentorRoleId,
            'timezone' => $timezone,
            'is_active' => 1,
        ]);

        // Available every day of the week 09:00-17:00 local time, so the test
        // doesn't depend on which weekday "today" happens to be.
        $availability = [];
        for ($day = 0; $day <= 6; $day++) {
            $availability[] = ['day' => $day, 'start' => '09:00', 'end' => '17:00'];
        }

        $mentorId = Mentor::insert([
            'user_id' => $userId,
            'bio' => 'Export strategy mentor',
            'expertise' => json_encode(['Export']),
            'availability' => json_encode($availability),
            'session_length_minutes' => 60,
        ]);

        return Mentor::find($mentorId);
    }

    private function makeCompany(string $suffix): array
    {
        $userId = User::insert([
            'email' => "founder-mentor-{$suffix}@example.com",
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'F', 'last_name' => $suffix,
            'role_id' => self::$applicantRoleId,
            'is_active' => 1,
        ]);
        $companyId = Company::insert(['name' => "Co {$suffix}", 'owner_user_id' => $userId, 'status' => 'ACTIVE', 'hs_codes' => '[]']);

        return Company::find($companyId);
    }

    public function test_save_profile_filters_out_malformed_availability_rules(): void
    {
        $mentorUserId = User::insert([
            'email' => 'mentor-profile@example.com',
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'M', 'last_name' => 'P',
            'role_id' => self::$mentorRoleId,
            'is_active' => 1,
        ]);
        $service = new MentorService();

        $mentor = $service->saveProfile($mentorUserId, 'bio', ['Export'], [
            ['day' => 1, 'start' => '09:00', 'end' => '12:00'],
            ['day' => 9, 'start' => '09:00', 'end' => '12:00'], // invalid day
            ['day' => 2, 'start' => '14:00', 'end' => '10:00'], // end before start
            ['day' => 3, 'start' => 'bad', 'end' => '12:00'],
        ], 45);

        $saved = json_decode($mentor['availability'], true);
        $this->assertCount(1, $saved);
        $this->assertSame(1, $saved[0]['day']);
    }

    public function test_available_slots_are_generated_in_the_mentors_timezone_and_converted_to_utc(): void
    {
        $mentor = $this->makeMentor('a', 'Asia/Tehran');
        $service = new MentorService();

        $slots = $service->availableSlots($mentor, 7);

        $this->assertNotEmpty($slots);

        foreach ($slots as $slot) {
            $localHour = (int) (new DateTimeImmutable($slot['start_utc'], new DateTimeZone('UTC')))
                ->setTimezone(new DateTimeZone('Asia/Tehran'))
                ->format('H');
            $this->assertGreaterThanOrEqual(9, $localHour);
            $this->assertLessThan(17, $localHour);
        }
    }

    public function test_available_slots_exclude_already_booked_times(): void
    {
        $mentor = $this->makeMentor('b');
        $company = $this->makeCompany('b');
        $service = new MentorService();

        $slots = $service->availableSlots($mentor, 7);
        $this->assertNotEmpty($slots);
        $target = $slots[0];

        $service->bookSession($mentor['id'], $company['id'], $target['start_utc'], $target['end_utc'], 'agenda');

        $slotsAfter = $service->availableSlots($mentor, 7);
        $startTimes = array_column($slotsAfter, 'start_utc');

        $this->assertNotContains($target['start_utc'], $startTimes);
    }

    public function test_book_session_rejects_a_slot_in_the_past(): void
    {
        $mentor = $this->makeMentor('c');
        $company = $this->makeCompany('c');
        $service = new MentorService();

        $this->expectException(\InvalidArgumentException::class);
        $service->bookSession($mentor['id'], $company['id'], '2000-01-01 09:00:00', '2000-01-01 10:00:00', 'agenda');
    }

    public function test_double_booking_the_same_mentor_slot_is_rejected_by_the_database_constraint(): void
    {
        $mentor = $this->makeMentor('d');
        $companyA = $this->makeCompany('d1');
        $companyB = $this->makeCompany('d2');
        $service = new MentorService();

        $slots = $service->availableSlots($mentor, 7);
        $target = $slots[0];

        // First booking succeeds.
        $service->bookSession($mentor['id'], $companyA['id'], $target['start_utc'], $target['end_utc'], 'first');

        // A second, independent request for the exact same mentor+slot (simulating
        // two users racing for the same slot) must be rejected, proving the
        // uniqueness guarantee lives in the database, not just in the
        // "is this slot in availableSlots()" check the first caller already passed.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/رزرو شد/');
        $service->bookSession($mentor['id'], $companyB['id'], $target['start_utc'], $target['end_utc'], 'second');
    }

    public function test_cancelling_a_session_frees_the_slot_for_rebooking(): void
    {
        $mentor = $this->makeMentor('e');
        $companyA = $this->makeCompany('e1');
        $companyB = $this->makeCompany('e2');
        $service = new MentorService();

        $slots = $service->availableSlots($mentor, 7);
        $target = $slots[0];

        $first = $service->bookSession($mentor['id'], $companyA['id'], $target['start_utc'], $target['end_utc'], 'first');
        $service->cancelSession($first['id'], $mentor['user_id']);

        $second = $service->bookSession($mentor['id'], $companyB['id'], $target['start_utc'], $target['end_utc'], 'second');

        $this->assertSame('SCHEDULED', $second['status']);
    }

    public function test_record_outcome_marks_session_completed_with_summary_and_action_plan(): void
    {
        $mentor = $this->makeMentor('f');
        $company = $this->makeCompany('f');
        $service = new MentorService();
        $slots = $service->availableSlots($mentor, 7);
        $session = $service->bookSession($mentor['id'], $company['id'], $slots[0]['start_utc'], $slots[0]['end_utc'], 'agenda');

        $updated = $service->recordOutcome($session['id'], $mentor['user_id'], 'Great discussion', 'Follow up next week');

        $this->assertSame('COMPLETED', $updated['status']);
        $this->assertSame('Great discussion', $updated['summary']);
    }

    public function test_record_outcome_rejects_a_different_mentor(): void
    {
        $mentor = $this->makeMentor('g');
        $otherMentorUser = User::insert([
            'email' => 'other-mentor@example.com',
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'O', 'last_name' => 'M',
            'role_id' => self::$mentorRoleId,
            'is_active' => 1,
        ]);
        $company = $this->makeCompany('g');
        $service = new MentorService();
        $slots = $service->availableSlots($mentor, 7);
        $session = $service->bookSession($mentor['id'], $company['id'], $slots[0]['start_utc'], $slots[0]['end_utc'], 'agenda');

        $this->expectException(\RuntimeException::class);
        $service->recordOutcome($session['id'], $otherMentorUser, 'x', 'y');
    }

    public function test_cancel_session_rejects_a_different_mentor(): void
    {
        $mentor = $this->makeMentor('h');
        $otherMentorUser = User::insert([
            'email' => 'other-mentor-cancel@example.com',
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'O', 'last_name' => 'C',
            'role_id' => self::$mentorRoleId,
            'is_active' => 1,
        ]);
        $company = $this->makeCompany('h');
        $service = new MentorService();
        $slots = $service->availableSlots($mentor, 7);
        $session = $service->bookSession($mentor['id'], $company['id'], $slots[0]['start_utc'], $slots[0]['end_utc'], 'agenda');

        $this->expectException(\RuntimeException::class);
        $service->cancelSession($session['id'], $otherMentorUser);
    }
}
