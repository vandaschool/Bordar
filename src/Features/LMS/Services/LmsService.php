<?php

declare(strict_types=1);

namespace App\Features\LMS\Services;

use App\Core\AuditLog;
use App\Features\LMS\Models\Course;
use App\Features\LMS\Models\Lesson;

final class LmsService
{
    public function createCourse(string $title, string $description, ?string $cohortId, string $createdByUserId): array
    {
        $id = Course::insert([
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'cohort_id' => $cohortId,
            'created_by_id' => $createdByUserId,
        ]);

        AuditLog::record('lms.course_created', 'Course', $id, null, ['title' => $title]);

        return Course::find($id);
    }

    /** @param array<string, mixed> $content */
    public function addLesson(string $courseId, string $title, string $description, string $type, array $content, string $createdByUserId): array
    {
        if (!in_array($type, ['VIDEO', 'TEXT', 'QUIZ', 'EXTERNAL_LINK'], true)) {
            throw new \InvalidArgumentException('نوع درس نامعتبر است.');
        }

        if (in_array($type, ['VIDEO', 'EXTERNAL_LINK'], true)) {
            $url = (string) ($content['url'] ?? '');
            $scheme = parse_url($url, PHP_URL_SCHEME);

            // Rendered later as a plain href; rejecting anything but http(s)
            // blocks a javascript:/data: URI from ending up in the DOM even
            // though only an Admin can reach this endpoint.
            if (!in_array($scheme, ['http', 'https'], true)) {
                throw new \InvalidArgumentException('لینک باید با http:// یا https:// شروع شود.');
            }
        }

        $id = Lesson::insert([
            'course_id' => $courseId,
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'type' => $type,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
            'order' => Lesson::nextOrder($courseId),
            'created_by_id' => $createdByUserId,
        ]);

        AuditLog::record('lms.lesson_added', 'Lesson', $id, null, ['course_id' => $courseId, 'title' => $title]);

        return Lesson::find($id);
    }
}
