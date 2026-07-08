<?php

declare(strict_types=1);

namespace App\Features\LMS\Models;

use App\Core\Model;

final class Lesson extends Model
{
    protected static string $table = 'lessons';

    /** @return array<int, array<string, mixed>> */
    public static function forCourse(string $courseId): array
    {
        $sql = 'SELECT * FROM `lessons` WHERE `course_id` = :course_id AND `deleted_at` IS NULL ORDER BY `order` ASC';
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['course_id' => $courseId]);

        return $stmt->fetchAll();
    }

    public static function nextOrder(string $courseId): int
    {
        $sql = 'SELECT COALESCE(MAX(`order`), 0) + 1 FROM `lessons` WHERE `course_id` = :course_id AND `deleted_at` IS NULL';
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['course_id' => $courseId]);

        return (int) $stmt->fetchColumn();
    }
}
