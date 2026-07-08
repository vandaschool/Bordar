<?php

declare(strict_types=1);

namespace App\Features\Mentor\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Features\Company\Models\Company;
use App\Features\Mentor\Models\Mentor;
use App\Features\Mentor\Models\MentorSession;
use App\Features\Mentor\Services\MentorService;

final class MentorProfileController extends Controller
{
    private MentorService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new MentorService();
    }

    public function edit(): void
    {
        $mentor = Mentor::findByUserId(Auth::id());

        $this->render('Mentor::profile-edit', [
            'mentor' => $mentor,
            'availability' => $mentor !== null ? (json_decode($mentor['availability'] ?? '[]', true) ?: []) : [],
            'expertise' => $mentor !== null ? (json_decode($mentor['expertise'] ?? '[]', true) ?: []) : [],
            'status' => Session::flash('status'),
        ]);
    }

    public function update(): void
    {
        $this->requireCsrf();

        $bio = (string) Request::input('bio', '');
        $expertise = array_filter(array_map('trim', explode(',', (string) Request::input('expertise', ''))));
        $sessionLength = (int) Request::input('session_length_minutes', 45);

        $days = $_POST['day'] ?? [];
        $starts = $_POST['start'] ?? [];
        $ends = $_POST['end'] ?? [];
        $availability = [];
        foreach ($days as $i => $day) {
            $availability[] = ['day' => (int) $day, 'start' => $starts[$i] ?? '', 'end' => $ends[$i] ?? ''];
        }

        $this->service->saveProfile(Auth::id(), $bio, array_values($expertise), $availability, $sessionLength);
        Session::flash('status', 'پروفایل و زمان‌های در دسترس شما ذخیره شد.');
        $this->redirect('/mentor/profile');
    }

    public function sessions(): void
    {
        $mentor = Mentor::findByUserId(Auth::id());
        $sessions = [];

        if ($mentor !== null) {
            $sessions = array_map(static function (array $s) {
                $s['company'] = Company::find($s['company_id']);

                return $s;
            }, MentorSession::forMentor($mentor['id']));
        }

        $this->render('Mentor::mentor-sessions', [
            'sessions' => $sessions,
            'viewerTz' => Auth::user()['timezone'] ?? 'Asia/Tehran',
            'status' => Session::flash('status'),
            'error' => Session::flash('error'),
        ]);
    }

    public function recordOutcome(string $sessionId): void
    {
        $this->requireCsrf();

        $summary = (string) Request::input('summary', '');
        $actionPlan = (string) Request::input('action_plan', '');

        try {
            $this->service->recordOutcome($sessionId, Auth::id(), $summary, $actionPlan);
            Session::flash('status', 'خلاصه جلسه ثبت شد.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/mentor/sessions');
    }

    public function cancel(string $sessionId): void
    {
        $this->requireCsrf();

        $this->service->cancelSession($sessionId, 'canceled by mentor');
        Session::flash('status', 'جلسه لغو شد.');
        $this->redirect('/mentor/sessions');
    }
}
