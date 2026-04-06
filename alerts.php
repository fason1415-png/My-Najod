<?php
declare(strict_types=1);
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';
require_login();

$alerts=db_read('alerts'); $users=db_read('users'); $devices=db_read('devices');
if(isset($_GET['create_demo']) && $_GET['create_demo']==='1'){
  $voiceSamples=['Yuragim qattiq siqilyapti, nafasim qisildi','Uyga o‘g‘ri kirdi, menga hujum qildi','Hovlimiz yonmoqda, hamma joy tutun','Yiqildim, boshim aylanyapti','Bizga yordam kerak, eshitayapsizlarmi'];
  $voice=$voiceSamples[array_rand($voiceSamples)];
  $u=$users[array_rand($users)]; $d=$devices[array_rand($devices)];
  $newAlert=build_alert_payload((string)($u['id']??''),(string)($d['device_id']??''),$voice);
  $newAlert['operator_comment']='Avtomatik demo SOS yaratildi. '.($newAlert['operator_comment']??'');
  $alerts[]=$newAlert; db_write('alerts',array_values($alerts)); header('Location: alerts.php'); exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $alertId=trim((string)($_POST['alert_id']??'')); $newStatus=trim((string)($_POST['status']??'Yangi'));
  $idx=db_find_index($alerts,$alertId); if($idx!==null){$alerts[$idx]['status']=$newStatus; db_write('alerts',array_values($alerts));}
  header('Location: alerts.php'); exit;
}
$q=trim((string)($_GET['q']??'')); $statusFilter=trim((string)($_GET['status']??''));
if($q!=='')$alerts=array_values(array_filter($alerts,fn($a)=>str_contains(mb_strtolower(implode(' ',array_map('strval',$a)),'UTF-8'),mb_strtolower($q,'UTF-8'))));
if($statusFilter!=='')$alerts=array_values(array_filter($alerts,fn($a)=>(($a['status']??'')===$statusFilter)));

$pageTitle='SOS Alertlar';$currentPage='alerts.php';include __DIR__.'/includes/header.php';include __DIR__.'/includes/sidebar.php';
?>
<main class="main-wrap"><?php include __DIR__.'/includes/navbar.php'; ?>
<section class="card"><div class="toolbar"><form method="get" class="actions" style="display:flex;gap:8px;flex:1;"><input class="input" name="q" value="<?= h($q) ?>" placeholder="Qidiruv..."><select name="status" style="max-width:220px;"><option value="">Barcha holatlar</option><?php foreach(statuses() as $st): ?><option value="<?= h($st) ?>" <?= $statusFilter===$st?'selected':'' ?>><?= h($st) ?></option><?php endforeach; ?></select><button class="btn secondary" type="submit">Filter</button></form><a class="btn danger" href="alerts.php?create_demo=1">+ Demo SOS</a></div>
<div class="table-wrap" style="margin-top:12px;"><table><thead><tr><th>Alert</th><th>Foydalanuvchi</th><th>Qurilma</th><th>Voice text</th><th>AI</th><th>Service</th><th>Status</th><th>Lokatsiya</th><th>Amal</th></tr></thead><tbody>
<?php foreach(array_reverse($alerts) as $a): ?><tr>
<td><strong><?= h($a['alert_id']??'-') ?></strong><br><small class="muted"><?= h($a['created_at']??'-') ?></small></td>
<td><?= h(find_name_by_id($users,(string)($a['user_id']??''),'id','full_name')) ?></td>
<td><?= h(find_name_by_id($devices,(string)($a['device_id']??''),'device_id','device_name')) ?></td>
<td><?= h($a['voice_text']??'-') ?></td>
<td><?= h($a['ai_result']??'-') ?><br><small class="muted">Conf: <?= h((string)($a['confidence']??'-')) ?></small></td>
<td><?= h($a['service']??'-') ?></td>
<td><form method="post" style="display:flex;gap:8px;align-items:center;"><input type="hidden" name="alert_id" value="<?= h((string)$a['alert_id']) ?>"><select name="status" style="min-width:130px;"><?php foreach(statuses() as $st): ?><option value="<?= h($st) ?>" <?= ($a['status']??'')===$st?'selected':'' ?>><?= h($st) ?></option><?php endforeach; ?></select><button class="btn secondary" type="submit">Saqlash</button></form></td>
<td><a class="btn secondary" target="_blank" href="https://maps.google.com/?q=<?= urlencode((string)$a['latitude']) ?>,<?= urlencode((string)$a['longitude']) ?>">Map</a><small class="muted" style="display:block;margin-top:4px;"><?= h((string)$a['latitude']) ?>, <?= h((string)$a['longitude']) ?></small></td>
<td><a class="btn primary" href="alert-view.php?id=<?= urlencode((string)$a['alert_id']) ?>">Ko‘rish</a></td>
</tr><?php endforeach; ?>
</tbody></table></div></section></main><?php include __DIR__.'/includes/footer.php'; ?>
