<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
if (isset($_GET['delete']) && $_GET['delete'] !== '') { db_delete('devices', (string)$_GET['delete']); header('Location: devices.php'); exit; }
$devices = db_read('devices');
$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') $devices = array_values(array_filter($devices, fn($d) => str_contains(mb_strtolower(implode(' ', $d), 'UTF-8'), mb_strtolower($q, 'UTF-8'))));
$pageTitle='Qurilmalar'; $currentPage='devices.php'; include __DIR__.'/includes/header.php'; include __DIR__.'/includes/sidebar.php';
?>
<main class="main-wrap"><?php include __DIR__.'/includes/navbar.php'; ?>
<section class="card"><div class="toolbar"><form method="get" class="actions" style="flex:1;"><input class="input" name="q" value="<?= h($q) ?>" placeholder="Qidiruv..."></form><a class="btn primary" href="device-form.php">+ Yangi qurilma</a></div>
<div class="table-wrap" style="margin-top:12px;"><table><thead><tr><th>ID</th><th>Nomi</th><th>SIM</th><th>Battery</th><th>GPS</th><th>Mikrofon</th><th>Status</th><th>Amal</th></tr></thead><tbody>
<?php foreach($devices as $d): ?><tr><td><?= h($d['device_id']??'-') ?></td><td><?= h($d['device_name']??'-') ?></td><td><?= h($d['sim_number']??'-') ?></td><td><?= h((string)($d['battery_level']??0)) ?>%</td><td><?= h($d['gps_status']??'-') ?></td><td><?= h($d['microphone_status']??'-') ?></td><td><span class="<?= badge_class($d['status']??'Yangi') ?>"><?= h($d['status']??'-') ?></span></td><td><a class="btn secondary" href="device-form.php?id=<?= urlencode((string)$d['device_id']) ?>">Tahrirlash</a> <a class="btn danger" href="devices.php?delete=<?= urlencode((string)$d['device_id']) ?>" onclick="return confirm('O‘chirilsinmi?')">O‘chirish</a></td></tr><?php endforeach; ?>
</tbody></table></div></section></main><?php include __DIR__.'/includes/footer.php'; ?>
