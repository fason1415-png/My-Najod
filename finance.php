<?php
declare(strict_types=1);
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';
require_login();
$finance=db_read('finance');
if($_SERVER['REQUEST_METHOD']==='POST'){
$finance=['monthly_cost'=>(int)($_POST['monthly_cost']??0),'monthly_revenue'=>(int)($_POST['monthly_revenue']??0),'profit'=>(int)($_POST['profit']??0),'active_users'=>(int)($_POST['active_users']??0),'devices_sold'=>(int)($_POST['devices_sold']??0),'subscription_income'=>(int)($_POST['subscription_income']??0),'device_sales_income'=>(int)($_POST['device_sales_income']??0),'government_contract_income'=>(int)($_POST['government_contract_income']??0)]; if($finance['profit']===0)$finance['profit']=$finance['monthly_revenue']-$finance['monthly_cost']; db_write('finance',$finance); header('Location: finance.php'); exit;}
$growth=($finance['monthly_cost']??0)>0?round(((($finance['monthly_revenue']??0)-($finance['monthly_cost']??0))/($finance['monthly_cost']??1))*100,1):0;
$pageTitle='Moliyaviy tahlil';$currentPage='finance.php';include __DIR__.'/includes/header.php';include __DIR__.'/includes/sidebar.php';
?>
<main class="main-wrap"><?php include __DIR__.'/includes/navbar.php'; ?>
<section class="grid cols-4"><article class="card metric red"><div class="label">Oylik xarajat</div><div class="value"><?= money((float)($finance['monthly_cost']??0)) ?></div></article><article class="card metric blue"><div class="label">Oylik tushum</div><div class="value"><?= money((float)($finance['monthly_revenue']??0)) ?></div></article><article class="card metric green"><div class="label">Foyda</div><div class="value"><?= money((float)($finance['profit']??0)) ?></div></article><article class="card metric purple"><div class="label">O‘sish</div><div class="value"><?= h((string)$growth) ?>%</div></article></section>
<section class="card"><h3 style="margin-top:0;">Moliyaviy parametrlar</h3><form method="post" class="form-grid"><?php foreach(['monthly_cost','monthly_revenue','profit','active_users','devices_sold','subscription_income','device_sales_income','government_contract_income'] as $key): ?><div><label class="muted"><?= h($key) ?></label><input class="input" type="number" name="<?= h($key) ?>" value="<?= h((string)($finance[$key]??0)) ?>"></div><?php endforeach; ?><div class="full"><button class="btn success" type="submit">Saqlash</button></div></form></section>
</main><?php include __DIR__.'/includes/footer.php'; ?>
