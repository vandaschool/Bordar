<?php

declare(strict_types=1);

namespace App\Features\LMS\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Features\Cohort\Models\Cohort;
use App\Features\LMS\Models\Course;
use App\Features\LMS\Models\Lesson;
use App\Features\LMS\Services\LmsService;

final class LmsAdminController extends Controller
{
    private LmsService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new LmsService();
    }

    public function index(): void
    {
        $courses = array_map(static function (array $c) {
            $c['cohort'] = $c['cohort_id'] !== null ? Cohort::find($c['cohort_id']) : null;
            $c['lesson_count'] = count(Lesson::forCourse($c['id']));

            return $c;
        }, Course::all());

        $this->render('LMS::admin-index', [
            'courses' => $courses,
            'cohorts' => Cohort::allOrderedByStartDate(),
            'status' => Session::flash('status'),
        ]);
    }

    public function storeCourse(): void
    {
        $this->requireCsrf();

        $title = (string) Request::input('title', '');
        $description = (string) Request::input('description', '');
        $cohortId = (string) Request::input('cohort_id', '');

        if (trim($title) === '') {
            Session::flash('error', 'عنوان دوره الزامی است.');
            $this->redirect('/admin/lms');

            return;
        }

        $this->service->createCourse($title, $description, $cohortId !== '' ? $cohortId : null, Auth::id());
        Session::flash('status', 'دوره ایجاد شد.');
        $this->redirect('/admin/lms');
    }

    public function showCourse(string $id): void
    {
        $course = Course::find($id);

        if ($course === null) {
            $this->redirect('/admin/lms');

            return;
        }

        $this->render('LMS::admin-course', [
            'course' => $course,
            'lessons' => Lesson::forCourse($id),
            'status' => Session::flash('status'),
            'error' => Session::flash('error'),
        ]);
    }

    public function addLesson(string $courseId): void
    {
        $this->requireCsrf();

        $title = (string) Request::input('title', '');
        $description = (string) Request::input('description', '');
        $type = (string) Request::input('type', 'TEXT');
        $body = (string) Request::input('body', '');
        $url = (string) Request::input('url', '');

        $content = in_array($type, ['VIDEO', 'EXTERNAL_LINK'], true) ? ['url' => $url] : ['body' => $body];

        try {
            $this->service->addLesson($courseId, $title, $description, $type, $content, Auth::id());
            Session::flash('status', 'درس اضافه شد.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/admin/lms/' . $courseId);
    }
}
