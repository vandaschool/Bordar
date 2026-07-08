<?php

declare(strict_types=1);

namespace App\Features\Payment\Models;

use App\Core\Model;

final class Payment extends Model
{
    protected static string $table = 'payments';

    /** @return array<int, array<string, mixed>> */
    public static function forInvoice(string $invoiceId): array
    {
        $sql = 'SELECT * FROM `payments` WHERE `invoice_id` = :invoice_id AND `deleted_at` IS NULL ORDER BY `installment_number` ASC, `created_at` ASC';
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['invoice_id' => $invoiceId]);

        return $stmt->fetchAll();
    }

    public static function findPendingForInstallment(string $invoiceId, int $installmentNumber, string $method): ?array
    {
        $sql = "SELECT * FROM `payments`
                WHERE `invoice_id` = :invoice_id AND `installment_number` = :n AND `method` = :method
                  AND `status` = 'PENDING' AND `deleted_at` IS NULL
                ORDER BY `created_at` DESC LIMIT 1";
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['invoice_id' => $invoiceId, 'n' => $installmentNumber, 'method' => $method]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function isInstallmentPaid(string $invoiceId, int $installmentNumber): bool
    {
        $sql = "SELECT COUNT(*) FROM `payments`
                WHERE `invoice_id` = :invoice_id AND `installment_number` = :n AND `status` = 'PAID' AND `deleted_at` IS NULL";
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['invoice_id' => $invoiceId, 'n' => $installmentNumber]);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    public static function findByTransactionId(string $transactionId): ?array
    {
        return static::findBy('transaction_id', $transactionId);
    }

    /** @return array<int, array<string, mixed>> */
    public static function pendingManualTransfers(): array
    {
        $sql = "SELECT * FROM `payments`
                WHERE `method` = 'MANUAL_BANK_TRANSFER' AND `status` = 'PENDING' AND `deleted_at` IS NULL
                ORDER BY `created_at` ASC";

        return static::pdo()->query($sql)->fetchAll();
    }
}
