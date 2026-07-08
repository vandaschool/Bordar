<?php

declare(strict_types=1);

namespace App\Features\LMS\Controllers;

use App\Core\Controller;
use App\Core\Tenant;
use App\Features\Cohort\Models\CompanyCohort;
use App\Features\LMS\Models\Course;
use App\Features\LMS\Models\Lesson;

final class LmsController extends Controller
{
    private function myCohortId(): ?string
    {
        $companyId = Tenant::companyId();

        if ($companyId === null) {
            return null;
        }

        return CompanyCohort::latestAcceptedForCompany($companyId)['cohort_id'] ?? null;
    }

    public function index(): void
    {
        $cohortId = $this->myCohortId();
        $courses = $cohortId !== null ? Course::forCohort($cohortId) : [];

        $courses = array_map(static function (array $c) {
            $c['lesson_count'] = count(Lesson::forCourse($c['id']));

            return $c;
        }, $courses);

        $this->render('LMS::index', ['courses' => $courses, 'hasCohort' => $cohortId !== null]);
    }

    public function show(string $id): void
    {
        $cohortId = $this->myCohortId();
        $course = Course::find($id);

        if ($course === null || $course['cohort_id'] !== $cohortId) {
            $this->redirect('/lms');

            return;
        }

        $this->render('LMS::course', ['course' => $course, 'lessons' => Lesson::forCourse($id)]);
    }
}
