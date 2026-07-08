<?php

declare(strict_types=1);

namespace App\Features\Mentor\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Tenant;
use App\Features\Auth\Models\User;
use App\Features\Mentor\Models\Mentor;
use App\Features\Mentor\Models\MentorSession;
use App\Features\Mentor\Services\MentorService;
use App\Lib\DateConverter;

final class MentorController extends Controller
{
    private MentorService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new MentorService();
    }

    public function index(): void
    {
        $mentors = array_map(static function (array $m) {
            $m['user'] = User::find($m['user_id']);

            return $m;
        }, array_filter(Mentor::allActive(), static fn ($m) => $m['availability'] !== null && $m['availability'] !== '[]'));

        $this->render('Mentor::index', ['mentors' => $mentors]);
    }

    public function showSlots(string $mentorId): void
    {
        $mentor = Mentor::find($mentorId);

        if ($mentor === null) {
            $this->redirect('/mentors');

            return;
        }

        $viewerTz = Auth::user()['timezone'] ?? 'Asia/Tehran';
        $slots = $this->service->availableSlots($mentor);

        $slotsByDay = [];
        foreach ($slots as $slot) {
            $dayLabel = DateConverter::toJalaliDate($slot['start_utc'], $viewerTz);
            $slotsByDay[$dayLabel][] = [
                'start_utc' => $slot['start_utc'],
                'end_utc' => $slot['end_utc'],
                'label' => DateConverter::toJalali($slot['start_utc'], 'H:i', $viewerTz),
            ];
        }

        $this->render('Mentor::slots', [
            'mentor' => $mentor,
            'mentorUser' => User::find($mentor['user_id']),
            'slotsByDay' => $slotsByDay,
            'viewerTz' => $viewerTz,
            'error' => Session::flash('error'),
        ]);
    }

    public function book(string $mentorId): void
    {
        $this->requireCsrf();

        $companyId = Tenant::companyId();
        $startUtc = (string) Request::input('start_utc', '');
        $endUtc = (string) Request::input('end_utc', '');
        $agenda = (string) Request::input('agenda', '');

        try {
            $this->service->bookSession($mentorId, $companyId, $startUtc, $endUtc, $agenda);
            Session::flash('status', 'جلسه با موفقیت رزرو شد.');
            $this->redirect('/mentors/my-sessions');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/mentors/' . $mentorId . '/slots');
        }
    }

    public function mySessions(): void
    {
        $companyId = Tenant::companyId();
        $sessions = array_map(function (array $s) {
            $mentor = Mentor::find($s['mentor_id']);
            $s['mentor_user'] = $mentor !== null ? User::find($mentor['user_id']) : null;

            return $s;
        }, $companyId !== null ? MentorSession::forCompany($companyId) : []);

        $this->render('Mentor::my-sessions', [
            'sessions' => $sessions,
            'viewerTz' => Auth::user()['timezone'] ?? 'Asia/Tehran',
            'status' => Session::flash('status'),
        ]);
    }
}
