<?php

declare(strict_types=1);

namespace App\Features\SupportTicket\Models;

use App\Core\Model;

final class SupportTicketMessage extends Model
{
    protected static string $table = 'support_ticket_messages';

    protected static bool $softDeletes = false;

    /**
     * @return array<int, array<string, mixed>> Ordered thread; internal
     * staff-only notes are excluded unless $includeInternal is true.
     */
    public static function forTicket(string $ticketId, bool $includeInternal): array
    {
        $sql = 'SELECT * FROM `support_ticket_messages` WHERE `ticket_id` = :ticket_id';
        if (!$includeInternal) {
            $sql .= ' AND `is_internal` = 0';
        }
        $sql .= ' ORDER BY `created_at` ASC';

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['ticket_id' => $ticketId]);

        return $stmt->fetchAll();
    }
}
