<?php
declare(strict_types=1);
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';
require_login();
$services=db_read('services');$id=(string)($_GET['id']??'');$editIndex=$id!==''?db_find_index($services,$id):null;
$form=['service_id'=>'','name'=>'','hotline'=>'','region'=>'Toshkent shahri','sla_minutes'=>8,'status'=>'Qabul qilindi','description'=>'']; if($editIndex!==null)$form=array_merge($form,$services[$editIndex]);
if($_SERVER['REQUEST_METHOD']==='POST'){$payload=['service_id'=>trim($_POST['service_id']??''),'name'=>trim($_POST['name']??''),'hotline'=>trim($_POST['hotline']??''),'region'=>trim($_POST['region']??''),'sla_minutes'=>(int)($_POST['sla_minutes']??0),'status'=>trim($_POST['status']??'Qabul qilindi'),'description'=>trim($_POST['description']??'')];if($payload['service_id']==='')$payload['service_id']=db_next_id($services,'SRV-',3);$idx=db_find_index($services,$payload['service_id']);if($idx===null)$services[]=$payload;else $services[$idx]=$payload;db_write('services',array_values($services));header('Location: services.php');exit;}
$pageTitle=$id?'Xizmatni tahrirlash':'Yangi xizmat';$currentPage='services.php';include __DIR__.'/includes/header.php';include __DIR__.'/includes/sidebar.php';
?>
<main class="main-wrap"><?php include __DIR__.'/includes/navbar.php'; ?>
<form method="post" class="card"><div class="toolbar"><h3 style="margin:0;"><?= h($pageTitle) ?></h3><a class="btn secondary" href="services.php">Ortga</a></div>
<div class="form-grid" style="margin-top:14px;"><div><label class="muted">Service ID</label><input class="input" name="service_id" value="<?= h($form['service_id']) ?>" readonly></div><div><label class="muted">Xizmat nomi</label><input class="input" name="name" value="<?= h($form['name']) ?>" required></div><div><label class="muted">Hotline</label><input class="input" name="hotline" value="<?= h($form['hotline']) ?>"></div><div><label class="muted">Hudud</label><input class="input" name="region" value="<?= h($form['region']) ?>"></div><div><label class="muted">SLA</label><input class="input" type="number" min="1" name="sla_minutes" value="<?= h((string)$form['sla_minutes']) ?>"></div><div><label class="muted">Status</label><select name="status"><?php foreach(statuses() as $st): ?><option value="<?= h($st) ?>" <?= $form['status']===$st?'selected':'' ?>><?= h($st) ?></option><?php endforeach; ?></select></div><div class="full"><label class="muted">Izoh</label><textarea name="description"><?= h($form['description']) ?></textarea></div></div><div style="margin-top:14px;"><button class="btn success" type="submit">Saqlash</button></div></form>
</main><?php include __DIR__.'/includes/footer.php'; ?>
