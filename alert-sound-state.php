<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

header('Content-Type: application/json; charset=UTF-8');

$alerts = db_read('alerts');
$pendingCount = 0;
$latestPending = null;

for ($i = count($alerts) - 1; $i >= 0; $i--) {
    $row = $alerts[$i];
    $status = (string)($row['status'] ?? '');
    if ($status === 'Yangi') {
        $pendingCount++;
        if ($latestPending === null) {
            $latestPending = $row;
        }
    }
}

echo json_encode([
    'ok' => true,
    'pending_count' => $pendingCount,
    'latest_pending' => $latestPending ? [
        'alert_id' => $latestPending['alert_id'] ?? '',
        'service' => $latestPending['service'] ?? '',
        'created_at' => $latestPending['created_at'] ?? '',
        'voice_text' => $latestPending['voice_text'] ?? '',
    ] : null,
    'generated_at' => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
