<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Faqat POST ruxsat etiladi']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '{}', true);
if (!is_array($payload)) {
    $payload = [];
}

$voiceText = trim((string)($payload['voice_text'] ?? $_POST['voice_text'] ?? ''));
$userId = trim((string)($payload['user_id'] ?? $_POST['user_id'] ?? ''));
$deviceId = trim((string)($payload['device_id'] ?? $_POST['device_id'] ?? ''));
$locationText = trim((string)($payload['location_text'] ?? $_POST['location_text'] ?? ''));
$lat = isset($payload['latitude']) ? (float)$payload['latitude'] : (isset($_POST['latitude']) ? (float)$_POST['latitude'] : null);
$lng = isset($payload['longitude']) ? (float)$payload['longitude'] : (isset($_POST['longitude']) ? (float)$_POST['longitude'] : null);

if ($voiceText === '' || $userId === '' || $deviceId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'voice_text, user_id, device_id majburiy'], JSON_UNESCAPED_UNICODE);
    exit;
}

$alerts = db_read('alerts');
$alert = build_alert_payload($userId, $deviceId, $voiceText, $lat, $lng, $locationText);
$alerts[] = $alert;
db_write('alerts', array_values($alerts));

log_event('alerts', "SOS kelib tushdi: {$alert['alert_id']} | {$alert['service']} | {$alert['user_id']}");

echo json_encode([
    'ok' => true,
    'message' => 'SOS muvaffaqiyatli qabul qilindi',
    'alert' => $alert,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
