<?php
declare(strict_types=1);

function db_file(string $name): string
{
    return __DIR__ . '/../data/' . $name . '.json';
}

function db_read(string $name): array
{
    $path = db_file($name);
    if (!file_exists($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function db_write(string $name, array $data): bool
{
    $path = db_file($name);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return file_put_contents($path, $json . PHP_EOL) !== false;
}

function db_next_id(array $items, string $prefix = '', int $pad = 3): string
{
    $max = 0;
    foreach ($items as $item) {
        foreach (['id', 'alert_id', 'device_id', 'service_id'] as $idKey) {
            if (!empty($item[$idKey])) {
                preg_match('/(\d+)$/', (string)$item[$idKey], $m);
                if (!empty($m[1])) {
                    $max = max($max, (int)$m[1]);
                }
            }
        }
    }
    $next = $max + 1;
    return $prefix . str_pad((string)$next, $pad, '0', STR_PAD_LEFT);
}

function db_find_index(array $items, string $idValue): ?int
{
    foreach ($items as $index => $item) {
        foreach (['id', 'alert_id', 'device_id', 'service_id'] as $idKey) {
            if (isset($item[$idKey]) && (string)$item[$idKey] === (string)$idValue) {
                return $index;
            }
        }
    }
    return null;
}

function db_delete(string $name, string $idValue): bool
{
    $items = db_read($name);
    $idx = db_find_index($items, $idValue);
    if ($idx === null) {
        return false;
    }
    array_splice($items, $idx, 1);
    return db_write($name, array_values($items));
}
