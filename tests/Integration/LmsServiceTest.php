<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Database;
use App\Core\Model;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Cohort\Models\Cohort;
use App\Features\LMS\Models\Course;
use App\Features\LMS\Models\Lesson;
use App\Features\LMS\Services\LmsService;
use PDO;
use PHPUnit\Framework\TestCase;

final class LmsServiceTest extends TestCase
{
    private static PDO $pdo;

    private static string $adminId;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = Database::connection();

        $role = Role::findByName('Admin');
        $roleId = $role['id'] ?? Model::uuid();
        if ($role === null) {
            Role::insert(['id' => $roleId, 'name' => 'Admin', 'description' => 'Admin', 'permissions' => '["*"]']);
        }

        self::$adminId = User::insert([
            'email' => 'lms-admin@example.com',
            'password_hash' => password_hash('Password123', PASSWORD_BCRYPT),
            'first_name' => 'A', 'last_name' => 'D',
            'role_id' => $roleId,
            'is_active' => 1,
        ]);
    }

    protected function setUp(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['lessons', 'courses', 'cohorts'] as $table) {
            self::$pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    public function test_lessons_are_ordered_sequentially_as_added(): void
    {
        $cohortId = Cohort::insert(['name' => 'C1', 'start_date' => '2026-01-01 00:00:00', 'end_date' => '2026-06-01 00:00:00', 'status' => 'ACTIVE']);
        $service = new LmsService();
        $course = $service->createCourse('Export Basics', 'desc', $cohortId, self::$adminId);

        $l1 = $service->addLesson($course['id'], 'Intro', '', 'TEXT', ['body' => 'welcome'], self::$adminId);
        $l2 = $service->addLesson($course['id'], 'Advanced', '', 'VIDEO', ['url' => 'https://example.com/v'], self::$adminId);

        $lessons = Lesson::forCourse($course['id']);

        $this->assertSame($l1['id'], $lessons[0]['id']);
        $this->assertSame($l2['id'], $lessons[1]['id']);
        $this->assertSame(1, $lessons[0]['order']);
        $this->assertSame(2, $lessons[1]['order']);
    }

    public function test_course_is_scoped_to_its_cohort(): void
    {
        $cohortA = Cohort::insert(['name' => 'A', 'start_date' => '2026-01-01 00:00:00', 'end_date' => '2026-06-01 00:00:00', 'status' => 'ACTIVE']);
        $cohortB = Cohort::insert(['name' => 'B', 'start_date' => '2026-01-01 00:00:00', 'end_date' => '2026-06-01 00:00:00', 'status' => 'ACTIVE']);
        $service = new LmsService();
        $service->createCourse('Course A', '', $cohortA, self::$adminId);
        $service->createCourse('Course B', '', $cohortB, self::$adminId);

        $coursesForA = Course::forCohort($cohortA);

        $this->assertCount(1, $coursesForA);
        $this->assertSame('Course A', $coursesForA[0]['title']);
    }

    public function test_invalid_lesson_type_is_rejected(): void
    {
        $cohortId = Cohort::insert(['name' => 'C', 'start_date' => '2026-01-01 00:00:00', 'end_date' => '2026-06-01 00:00:00', 'status' => 'ACTIVE']);
        $service = new LmsService();
        $course = $service->createCourse('C', '', $cohortId, self::$adminId);

        $this->expectException(\InvalidArgumentException::class);
        $service->addLesson($course['id'], 'x', '', 'NOT_A_TYPE', [], self::$adminId);
    }
}
