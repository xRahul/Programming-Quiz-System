<?php

declare(strict_types=1);

/**
 * T5.3 audit trail helper. Writes one row per destructive admin action.
 *
 * Never throws: any failure is reported via error_log() and swallowed so
 * auditing can never break the action it is recording.
 */
function audit_log(string $action, string $detail = ''): void
{
    global $pdo;

    try {
        if (!$pdo instanceof PDO) {
            error_log('[audit] no PDO handle available; audit row for "' . $action . '" not recorded');
            return;
        }

        $actor = isset($_SESSION['login_username']) && is_string($_SESSION['login_username'])
            ? $_SESSION['login_username']
            : 'system';

     //column limits are actor 50 / action 50 / detail 255 (migration 007);
     //char-truncate with substr (VARCHAR(n) counts characters) so oversized
     //values can never raise a strict-mode error
        $stmt = $pdo->prepare("INSERT INTO audit_log (actor, action, detail) VALUES (:actor, :action, :detail)");
        $stmt->execute([
            'actor' => substr($actor, 0, 50),
            'action' => substr($action, 0, 50),
            'detail' => substr($detail, 0, 255),
        ]);
    } catch (Throwable $e) {
        error_log('[audit] failed to record "' . $action . '": ' . $e->getMessage());
    }
}
