<?php
declare(strict_types=1);
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';
require_login();
$devices=db_read('devices'); $users=db_read('users'); $id=(string)($_GET['id']??''); $editIndex=$id!==''?db_find_index($devices,$id):null;
$form=['device_id'=>'','device_name'=>'','sim_number'=>'','battery_level'=>85,'gps_status'=>'Faol','microphone_status'=>'Faol','last_signal_at'=>date('Y-m-d H:i:s'),'assigned_user_id'=>'','status'=>'Qabul qilindi'];
if($editIndex!==null)$form=array_merge($form,$devices[$editIndex]);
if($_SERVER['REQUEST_METHOD']==='POST'){
$payload=['device_id'=>trim($_POST['device_id']??''),'device_name'=>trim($_POST['device_name']??''),'sim_number'=>trim($_POST['sim_number']??''),'battery_level'=>(int)($_POST['battery_level']??0),'gps_status'=>trim($_POST['gps_status']??'Faol'),'microphone_status'=>trim($_POST['microphone_status']??'Faol'),'last_signal_at'=>trim($_POST['last_signal_at']??date('Y-m-d H:i:s')),'assigned_user_id'=>trim($_POST['assigned_user_id']??''),'status'=>trim($_POST['status']??'Qabul qilindi')];
if($payload['device_id']==='')$payload['device_id']=db_next_id($devices,'DEV-',3);
$idx=db_find_index($devices,$payload['device_id']); if($idx===null)$devices[]=$payload; else $devices[$idx]=$payload; db_write('devices',array_values($devices)); header('Location: devices.php'); exit;}
$pageTitle=$id?'Qurilmani tahrirlash':'Yangi qurilma'; $currentPage='devices.php'; include __DIR__.'/includes/header.php'; include __DIR__.'/includes/sidebar.php';
?>
<main class="main-wrap"><?php include __DIR__.'/includes/navbar.php'; ?>
<form method="post" class="card"><div class="toolbar"><h3 style="margin:0;"><?= h($pageTitle) ?></h3><a class="btn secondary" href="devices.php">Ortga</a></div>
<div class="form-grid" style="margin-top:14px;">
<div><label class="muted">Device ID</label><input class="input" name="device_id" value="<?= h($form['device_id']) ?>" readonly></div>
<div><label class="muted">Qurilma nomi</label><input class="input" name="device_name" value="<?= h($form['device_name']) ?>" required></div>
<div><label class="muted">SIM</label><input class="input" name="sim_number" value="<?= h($form['sim_number']) ?>"></div>
<div><label class="muted">Battery</label><input class="input" type="number" min="0" max="100" name="battery_level" value="<?= h((string)$form['battery_level']) ?>"></div>
<div><label class="muted">GPS</label><select name="gps_status"><?php foreach(['Faol','Nofaol'] as $s): ?><option value="<?= h($s) ?>" <?= $form['gps_status']===$s?'selected':'' ?>><?= h($s) ?></option><?php endforeach; ?></select></div>
<div><label class="muted">Mikrofon</label><select name="microphone_status"><?php foreach(['Faol','Nofaol'] as $s): ?><option value="<?= h($s) ?>" <?= $form['microphone_status']===$s?'selected':'' ?>><?= h($s) ?></option><?php endforeach; ?></select></div>
<div><label class="muted">Oxirgi signal</label><input class="input" name="last_signal_at" value="<?= h($form['last_signal_at']) ?>"></div>
<div><label class="muted">User</label><select name="assigned_user_id"><option value="">Tanlanmagan</option><?php foreach($users as $u): ?><option value="<?= h($u['id']) ?>" <?= ($form['assigned_user_id']??'')===($u['id']??'')?'selected':'' ?>><?= h(($u['id']??'').' - '.($u['full_name']??'')) ?></option><?php endforeach; ?></select></div>
<div class="full"><label class="muted">Status</label><select name="status"><?php foreach(statuses() as $s): ?><option value="<?= h($s) ?>" <?= $form['status']===$s?'selected':'' ?>><?= h($s) ?></option><?php endforeach; ?></select></div>
</div><div style="margin-top:14px;"><button class="btn success" type="submit">Saqlash</button></div></form></main><?php include __DIR__.'/includes/footer.php'; ?>
