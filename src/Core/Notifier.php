<?php

declare(strict_types=1);

namespace App\Core;

use App\Features\Auth\Models\User;
use App\Features\Notification\Models\Notification;
use App\Lib\Mailer;
use App\Lib\SmsSender;

/**
 * Central dispatch point for every user-facing notification. The
 * Communication Matrix below is the single source of truth for which
 * channels a given business event fires on - add an event here once and
 * every call site gets consistent behavior instead of ad-hoc per-feature
 * channel decisions.
 */
final class Notifier
{
    /** event => [type, channels[]] */
    private const MATRIX = [
        'review.reviewer_assigned' => ['APPLICATION_STATUS_UPDATE', ['IN_APP', 'EMAIL']],
        'review.clarification_requested' => ['REVIEW_CLARIFICATION_REQUEST', ['IN_APP', 'EMAIL', 'SMS']],
        'review.clarification_responded' => ['REVIEW_CLARIFICATION_REQUEST', ['IN_APP']],
        'application.accepted' => ['APPLICATION_STATUS_UPDATE', ['IN_APP', 'EMAIL', 'SMS']],
        'application.rejected' => ['APPLICATION_STATUS_UPDATE', ['IN_APP', 'EMAIL']],
        'payment.due' => ['PAYMENT_DUE', ['IN_APP', 'EMAIL']],
        'payment.manual_submitted' => ['PAYMENT_DUE', ['IN_APP']],
        'payment.received' => ['PAYMENT_RECEIVED', ['IN_APP', 'EMAIL', 'SMS']],
        'payment.rejected' => ['PAYMENT_DUE', ['IN_APP', 'EMAIL']],
        'onboarding.welcome' => ['GENERAL_ANNOUNCEMENT', ['IN_APP', 'EMAIL']],
        'mentor.session_booked' => ['MENTOR_SESSION_REMINDER', ['IN_APP', 'EMAIL']],
        'mentor.session_completed' => ['MENTOR_SESSION_REMINDER', ['IN_APP']],
        'mentor.session_canceled' => ['MENTOR_SESSION_REMINDER', ['IN_APP', 'EMAIL']],
        'support_ticket.created' => ['SUPPORT_TICKET_UPDATE', ['IN_APP']],
        'support_ticket.replied' => ['SUPPORT_TICKET_UPDATE', ['IN_APP', 'EMAIL']],
    ];

    public static function send(string $event, string $userId, string $title, string $message, ?string $link = null): void
    {
        if (!isset(self::MATRIX[$event])) {
            return;
        }

        [$type, $channels] = self::MATRIX[$event];
        $user = User::find($userId);

        if ($user === null) {
            return;
        }

        foreach ($channels as $channel) {
            Notification::insert([
                'user_id' => $userId,
                'type' => $type,
                'channel' => $channel,
                'title' => $title,
                'message' => $message,
                'link' => $link,
            ]);

            match ($channel) {
                'EMAIL' => Mailer::send($user['email'], $title, '<div dir="rtl">' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</div>'),
                'SMS' => $user['phone_number'] !== null ? SmsSender::send($user['phone_number'], $title . "\n" . $message) : null,
                default => null,
            };
        }
    }

    /** Convenience for broadcasting to every user holding a given role (e.g. all Admins). */
    public static function sendToRole(string $event, string $roleId, string $title, string $message, ?string $link = null): void
    {
        foreach (User::where('role_id', $roleId) as $user) {
            self::send($event, $user['id'], $title, $message, $link);
        }
    }
}
