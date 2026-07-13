<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Audit trail logger. Records who did what, when.
 */
class Audit
{
    public static function log(
        string $action,
        ?string $table = null,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        try {
            Database::instance()->query(
                "INSERT INTO audit_logs
                    (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    Auth::id(),
                    $action,
                    $table,
                    $recordId,
                    $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                    $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                    Request::ip(),
                    Request::userAgent(),
                ]
            );
        } catch (\Throwable $e) {
            // Auditing must never break the main flow
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }
}
