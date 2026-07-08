<?php

declare(strict_types=1);

namespace App\Features\SupportTicket\Services;

use App\Core\AuditLog;
use App\Core\Notifier;
use App\Features\SupportTicket\Models\SupportTicket;
use App\Features\SupportTicket\Models\SupportTicketMessage;

final class SupportTicketService
{
    public const CATEGORIES = [
        'REGISTRATION' => 'ثبت‌نام', 'PAYMENT' => 'پرداخت', 'APPLICATION_FORM' => 'فرم درخواست',
        'DOCUMENTS' => 'اسناد', 'MENTORING_SESSION' => 'جلسات منتورینگ', 'ACCESS_ISSUE' => 'مشکل دسترسی',
        'TECHNICAL_ISSUE' => 'مشکل فنی', 'REVIEW_APPEAL' => 'اعتراض به داوری', 'OTHER' => 'سایر',
    ];

    public function create(string $userId, string $subject, string $description, string $category): array
    {
        $id = SupportTicket::insert([
            'user_id' => $userId,
            'subject' => $subject,
            'description' => $description,
            'category' => array_key_exists($category, self::CATEGORIES) ? $category : 'OTHER',
            'status' => 'OPEN',
            'created_by_id' => $userId,
        ]);

        AuditLog::record('support_ticket.created', 'SupportTicket', $id, null, ['subject' => $subject]);
        Notifier::send('support_ticket.created', $userId, 'تیکت شما ثبت شد', 'تیکت «' . $subject . '» با موفقیت ثبت شد و به‌زودی بررسی می‌شود.');

        return SupportTicket::find($id);
    }

    public function reply(string $ticketId, string $senderId, string $message, bool $isStaff, bool $isInternal = false): array
    {
        $ticket = SupportTicket::find($ticketId);

        if ($ticket === null) {
            throw new \RuntimeException('تیکت یافت نشد.');
        }

        SupportTicketMessage::insert([
            'ticket_id' => $ticketId,
            'sender_id' => $senderId,
            'message' => $message,
            'is_internal' => $isInternal ? 1 : 0,
        ]);

        if (!$isInternal) {
            $newStatus = $isStaff ? 'PENDING_USER_RESPONSE' : 'IN_PROGRESS';
            SupportTicket::update($ticketId, ['status' => $newStatus]);

            $notifyUserId = $isStaff ? $ticket['user_id'] : $ticket['assigned_to_id'];
            if ($notifyUserId !== null) {
                Notifier::send('support_ticket.replied', $notifyUserId, 'پاسخ جدید در تیکت', 'پاسخ جدیدی برای تیکت «' . $ticket['subject'] . '» ثبت شد.', '/tickets/' . $ticketId);
            }
        }

        AuditLog::record('support_ticket.replied', 'SupportTicket', $ticketId, null, ['is_internal' => $isInternal]);

        return SupportTicket::find($ticketId);
    }

    public function assign(string $ticketId, string $staffUserId): array
    {
        SupportTicket::update($ticketId, ['assigned_to_id' => $staffUserId, 'status' => 'IN_PROGRESS']);
        AuditLog::record('support_ticket.assigned', 'SupportTicket', $ticketId, null, ['assigned_to_id' => $staffUserId]);

        return SupportTicket::find($ticketId);
    }

    public function updateStatus(string $ticketId, string $status): array
    {
        if (!in_array($status, SupportTicket::STATUSES, true)) {
            throw new \InvalidArgumentException('وضعیت نامعتبر است.');
        }

        SupportTicket::update($ticketId, ['status' => $status]);
        AuditLog::record('support_ticket.status_changed', 'SupportTicket', $ticketId, null, ['status' => $status]);

        return SupportTicket::find($ticketId);
    }
}
