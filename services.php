<?php
declare(strict_types=1);
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';
require_login();
if(isset($_GET['delete'])&&$_GET['delete']!==''){db_delete('services',(string)$_GET['delete']);header('Location: services.php');exit;}
$services=db_read('services');$q=trim((string)($_GET['q']??''));if($q!=='')$services=array_values(array_filter($services,fn($s)=>str_contains(mb_strtolower(implode(' ',$s),'UTF-8'),mb_strtolower($q,'UTF-8'))));
$pageTitle='Xizmatlar routing';$currentPage='services.php';include __DIR__.'/includes/header.php';include __DIR__.'/includes/sidebar.php';
?>
<main class="main-wrap"><?php include __DIR__.'/includes/navbar.php'; ?>
<section class="card"><div class="toolbar"><form method="get" class="actions" style="flex:1;"><input class="input" name="q" value="<?= h($q) ?>" placeholder="Qidiruv..."></form><a class="btn primary" href="service-form.php">+ Yangi xizmat</a></div>
<div class="table-wrap" style="margin-top:12px;"><table><thead><tr><th>ID</th><th>Nomi</th><th>Hotline</th><th>Hudud</th><th>SLA</th><th>Status</th><th>Amal</th></tr></thead><tbody><?php foreach($services as $s): ?><tr><td><?= h($s['service_id']??'-') ?></td><td><?= h($s['name']??'-') ?></td><td><?= h($s['hotline']??'-') ?></td><td><?= h($s['region']??'-') ?></td><td><?= h((string)($s['sla_minutes']??'-')) ?> daqiqa</td><td><span class="<?= badge_class($s['status']??'Yangi') ?>"><?= h($s['status']??'-') ?></span></td><td><a class="btn secondary" href="service-form.php?id=<?= urlencode((string)$s['service_id']) ?>">Tahrirlash</a> <a class="btn danger" href="services.php?delete=<?= urlencode((string)$s['service_id']) ?>" onclick="return confirm('O‘chirasizmi?')">O‘chirish</a></td></tr><?php endforeach; ?></tbody></table></div></section>
</main><?php include __DIR__.'/includes/footer.php'; ?>
