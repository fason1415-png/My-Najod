<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

header('Content-Type: application/json; charset=UTF-8');

$alerts = db_read('alerts');
$users = db_read('users');
$finance = db_read('finance');
$statusStats = count_by_status($alerts);
$recent = array_slice(array_reverse($alerts), 0, 6);

$normalizedRecent = array_map(function (array $a) use ($users): array {
    return [
        'alert_id' => $a['alert_id'] ?? '',
        'service' => $a['service'] ?? '',
        'status' => $a['status'] ?? '',
        'created_at' => $a['created_at'] ?? '',
        'voice_text' => $a['voice_text'] ?? '',
        'user_name' => find_name_by_id($users, (string)($a['user_id'] ?? ''), 'id', 'full_name'),
        'latitude' => $a['latitude'] ?? null,
        'longitude' => $a['longitude'] ?? null,
    ];
}, $recent);

$latestLocation = null;
foreach ($normalizedRecent as $r) {
    if ($r['latitude'] !== null && $r['longitude'] !== null) {
        $latestLocation = [
            'alert_id' => $r['alert_id'],
            'latitude' => $r['latitude'],
            'longitude' => $r['longitude'],
            'service' => $r['service'],
            'status' => $r['status'],
        ];
        break;
    }
}

echo json_encode([
    'ok' => true,
    'metrics' => [
        'alerts' => count($alerts),
        'active_users' => (int)($finance['active_users'] ?? count($users)),
        'devices_sold' => (int)($finance['devices_sold'] ?? 0),
        'services' => count(db_read('services')),
    ],
    'status_stats' => $statusStats,
    'recent_alerts' => $normalizedRecent,
    'latest_location' => $latestLocation,
    'generated_at' => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
