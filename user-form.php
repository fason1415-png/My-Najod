<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$users = db_read('users');
$devices = db_read('devices');
$id = (string)($_GET['id'] ?? '');
$editIndex = $id !== '' ? db_find_index($users, $id) : null;
$form = ['id'=>'','full_name'=>'','phone'=>'','category'=>'Keksalar','address'=>'','emergency_contact'=>'','assigned_device_id'=>'','status'=>'Qabul qilindi','notes'=>''];
if ($editIndex !== null) $form = array_merge($form, $users[$editIndex]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'id' => trim($_POST['id'] ?? ''),
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'category' => trim($_POST['category'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
        'assigned_device_id' => trim($_POST['assigned_device_id'] ?? ''),
        'status' => trim($_POST['status'] ?? 'Qabul qilindi'),
        'notes' => trim($_POST['notes'] ?? ''),
    ];
    if ($payload['id'] === '') $payload['id'] = db_next_id($users, 'USR-', 3);
    $idx = db_find_index($users, $payload['id']);
    if ($idx === null) $users[] = $payload; else $users[$idx] = $payload;
    db_write('users', array_values($users));
    header('Location: users.php');
    exit;
}

$pageTitle = $id ? 'Foydalanuvchini tahrirlash' : 'Yangi foydalanuvchi';
$currentPage = 'users.php';
include __DIR__ . '/includes/header.php'; include __DIR__ . '/includes/sidebar.php';
?>
<main class="main-wrap"><?php include __DIR__ . '/includes/navbar.php'; ?>
<form method="post" class="card"><div class="toolbar"><h3 style="margin:0;"><?= h($pageTitle) ?></h3><a class="btn secondary" href="users.php">Ortga</a></div>
<div class="form-grid" style="margin-top:14px;">
<div><label class="muted">ID</label><input class="input" name="id" value="<?= h($form['id']) ?>" readonly></div>
<div><label class="muted">F.I.Sh</label><input class="input" name="full_name" value="<?= h($form['full_name']) ?>" required></div>
<div><label class="muted">Telefon</label><input class="input" name="phone" value="<?= h($form['phone']) ?>" required></div>
<div><label class="muted">Toifa</label><select name="category"><?php foreach(['Keksalar','Nogironlar','Ko‘zi ojizlar','Chekka hudud aholisi'] as $c): ?><option value="<?= h($c) ?>" <?= $form['category']===$c?'selected':'' ?>><?= h($c) ?></option><?php endforeach; ?></select></div>
<div><label class="muted">Favqulodda kontakt</label><input class="input" name="emergency_contact" value="<?= h($form['emergency_contact']) ?>"></div>
<div><label class="muted">Biriktirilgan qurilma</label><select name="assigned_device_id"><option value="">Tanlanmagan</option><?php foreach($devices as $d): ?><option value="<?= h($d['device_id']) ?>" <?= ($form['assigned_device_id']??'')===($d['device_id']??'')?'selected':'' ?>><?= h(($d['device_id']??'').' - '.($d['device_name']??'')) ?></option><?php endforeach; ?></select></div>
<div class="full"><label class="muted">Manzil</label><input class="input" name="address" value="<?= h($form['address']) ?>"></div>
<div><label class="muted">Status</label><select name="status"><?php foreach(statuses() as $s): ?><option value="<?= h($s) ?>" <?= $form['status']===$s?'selected':'' ?>><?= h($s) ?></option><?php endforeach; ?></select></div>
<div class="full"><label class="muted">Izoh</label><textarea name="notes"><?= h($form['notes']) ?></textarea></div>
</div><div style="margin-top:14px;"><button class="btn success" type="submit">Saqlash</button></div></form>
</main><?php include __DIR__ . '/includes/footer.php'; ?>
