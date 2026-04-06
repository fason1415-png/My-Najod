<?php
declare(strict_types=1);

require_once __DIR__ . '/json-db.php';

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function statuses(): array
{
    return ['Yangi', 'Qabul qilindi', 'Jarayonda', 'Yakunlandi', 'Bekor qilindi'];
}

function badge_class(string $status): string
{
    return match ($status) {
        'Yangi' => 'badge danger',
        'Qabul qilindi' => 'badge info',
        'Jarayonda' => 'badge warning',
        'Yakunlandi' => 'badge success',
        'Bekor qilindi' => 'badge muted',
        default => 'badge muted'
    };
}

function normalize_text(string $text): string
{
    $lower = mb_strtolower($text, 'UTF-8');
    $lower = str_replace(['`', '’', '‘', "'"], ' ', $lower);
    return preg_replace('/\s+/u', ' ', trim($lower)) ?? $lower;
}

function ai_classify(string $voiceText): array
{
    $text = normalize_text($voiceText);
    $medicalWords = ['yuragim', 'nafas', 'bosim', 'yiqildim', 'hushim ketdi'];
    $policeWords = ['ogri', 'o g ri', 'urdi', 'hujum', 'o‘g‘ri', 'o‘gri'];
    $fireWords = ['yonmoqda', 'olov', 'tutun', 'yoniyapti', 'yonyapti', 'yongin'];
    $urgentGeneric = ['yordam kerak', 'yordam bering', 'eshitayapsiz', 'bizga yordam'];

    $route = 'Maslahat / Operator';
    $urgency = 'O‘rtacha';
    $confidence = 0.71;
    $recommendation = 'Operator orqali qo‘shimcha savol-javob o‘tkazing.';

    foreach ($medicalWords as $w) {
        if (str_contains($text, $w)) {
            $route = 'Tez yordam';
            $urgency = 'Juda yuqori';
            $confidence = 0.93;
            $recommendation = 'Tez yordam brigadasini darhol yuborish.';
            break;
        }
    }
    foreach ($policeWords as $w) {
        if (str_contains($text, $w)) {
            $route = 'Militsiya';
            $urgency = 'Yuqori';
            $confidence = 0.9;
            $recommendation = 'Hududiy militsiyaga zudlik bilan yo‘naltirish.';
            break;
        }
    }
    foreach ($fireWords as $w) {
        if (str_contains($text, $w)) {
            $route = 'Yong‘in xizmati';
            $urgency = 'Kritik';
            $confidence = 0.95;
            $recommendation = 'Yong‘in xavfsizligi protokoli ishga tushirilsin.';
            break;
        }
    }

    if ($route === 'Maslahat / Operator') {
        foreach ($urgentGeneric as $w) {
            if (str_contains($text, $w)) {
                $route = 'Tez yordam';
                $urgency = 'Yuqori';
                $confidence = 0.84;
                $recommendation = 'Aniq kategoriya topilmasa ham shoshilinch yordam liniyasiga prioritet signal yuborish.';
                break;
            }
        }
    }

    return [
        'ai_result' => $route,
        'urgency' => $urgency,
        'confidence' => $confidence,
        'recommendation' => $recommendation,
        'service' => $route,
    ];
}

function money(float|int $value): string
{
    return number_format((float)$value, 0, '.', ' ') . ' $';
}

function count_by_status(array $alerts): array
{
    $out = array_fill_keys(statuses(), 0);
    foreach ($alerts as $alert) {
        $status = $alert['status'] ?? 'Yangi';
        if (!isset($out[$status])) {
            $out[$status] = 0;
        }
        $out[$status]++;
    }
    return $out;
}

function find_name_by_id(array $items, string $id, string $idKey, string $nameKey): string
{
    foreach ($items as $item) {
        if (($item[$idKey] ?? '') === $id) {
            return (string)($item[$nameKey] ?? $id);
        }
    }
    return $id;
}

function app_setting(string $key, mixed $default = null): mixed
{
    $settings = db_read('settings');
    return $settings[$key] ?? $default;
}

function log_event(string $type, string $message): void
{
    $logs = db_read('logs');
    $logs[] = [
        'id' => db_next_id($logs, 'LOG-', 4),
        'type' => $type,
        'message' => $message,
        'created_at' => date('Y-m-d H:i:s')
    ];
    db_write('logs', $logs);
}

function find_service_by_name(string $serviceName): ?array
{
    $services = db_read('services');
    foreach ($services as $service) {
        if (mb_strtolower((string)($service['name'] ?? ''), 'UTF-8') === mb_strtolower($serviceName, 'UTF-8')) {
            return $service;
        }
    }
    return null;
}

function build_alert_payload(string $userId, string $deviceId, string $voiceText, ?float $lat = null, ?float $lng = null): array
{
    $alerts = db_read('alerts');
    $ai = ai_classify($voiceText);
    $serviceRow = find_service_by_name((string)$ai['service']);

    $latitude = $lat ?? (41.28 + (mt_rand(-140, 140) / 1000));
    $longitude = $lng ?? (69.22 + (mt_rand(-140, 140) / 1000));

    return [
        'alert_id' => db_next_id($alerts, 'ALT-', 3),
        'user_id' => $userId,
        'device_id' => $deviceId,
        'voice_text' => $voiceText,
        'ai_result' => $ai['ai_result'],
        'urgency' => $ai['urgency'],
        'confidence' => $ai['confidence'],
        'service' => $ai['service'],
        'service_id' => $serviceRow['service_id'] ?? null,
        'service_hotline' => $serviceRow['hotline'] ?? null,
        'latitude' => round($latitude, 6),
        'longitude' => round($longitude, 6),
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'Yangi',
        'operator_comment' => (string)$ai['recommendation'],
        'route_note' => isset($serviceRow['hotline']) ? ("Avtomatik yo‘naltirish: " . $ai['service'] . " (" . $serviceRow['hotline'] . ")") : ("Avtomatik yo‘naltirish: " . $ai['service']),
    ];
}
