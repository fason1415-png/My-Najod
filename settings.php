<?php
declare(strict_types=1);
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';
require_login();
$settings=db_read('settings');
if($_SERVER['REQUEST_METHOD']==='POST'){
$oldKey=(string)($settings['openai_api_key']??'');$postedKey=trim((string)($_POST['openai_api_key']??''));$finalKey=$postedKey!==''?$postedKey:$oldKey;
$settings=['app_name'=>trim((string)($_POST['app_name']??'MY NajotLink')),'default_language'=>trim((string)($_POST['default_language']??'uz-Latn')),'operator_phone'=>trim((string)($_POST['operator_phone']??'+998')),'auto_ai_enabled'=>isset($_POST['auto_ai_enabled']),'notify_family'=>isset($_POST['notify_family']),'map_provider'=>trim((string)($_POST['map_provider']??'Google Maps')),'theme_mode'=>trim((string)($_POST['theme_mode']??'Dark Premium')),'demo_mode'=>isset($_POST['demo_mode']),'openai_enabled'=>isset($_POST['openai_enabled']),'openai_model'=>trim((string)($_POST['openai_model']??'gpt-4.1-mini')),'openai_api_key'=>$finalKey];
 db_write('settings',$settings); header('Location: settings.php'); exit;}
$pageTitle='Sozlamalar';$currentPage='settings.php';include __DIR__.'/includes/header.php';include __DIR__.'/includes/sidebar.php';
?>
<main class="main-wrap"><?php include __DIR__.'/includes/navbar.php'; ?>
<section class="card"><h3 style="margin-top:0;">Platforma sozlamalari</h3><form method="post" class="form-grid">
<div><label class="muted">App nomi</label><input class="input" name="app_name" value="<?= h((string)($settings['app_name']??'MY NajotLink')) ?>"></div>
<div><label class="muted">Asosiy til</label><input class="input" name="default_language" value="<?= h((string)($settings['default_language']??'uz-Latn')) ?>"></div>
<div><label class="muted">Operator telefoni</label><input class="input" name="operator_phone" value="<?= h((string)($settings['operator_phone']??'+998 71 203 11 73')) ?>"></div>
<div><label class="muted">Map provider</label><input class="input" name="map_provider" value="<?= h((string)($settings['map_provider']??'Google Maps')) ?>"></div>
<div><label class="muted">Theme mode</label><input class="input" name="theme_mode" value="<?= h((string)($settings['theme_mode']??'Dark Premium')) ?>"></div>
<div class="full" style="display:flex;gap:18px;flex-wrap:wrap;"><label><input type="checkbox" name="auto_ai_enabled" <?= !empty($settings['auto_ai_enabled'])?'checked':'' ?>> Auto AI classify</label><label><input type="checkbox" name="notify_family" <?= !empty($settings['notify_family'])?'checked':'' ?>> Oila notification</label><label><input type="checkbox" name="demo_mode" <?= !empty($settings['demo_mode'])?'checked':'' ?>> Demo mode</label><label><input type="checkbox" name="openai_enabled" <?= !empty($settings['openai_enabled'])?'checked':'' ?>> OpenAI AI classify</label></div>
<div><label class="muted">OpenAI model</label><input class="input" name="openai_model" value="<?= h((string)($settings['openai_model']??'gpt-4.1-mini')) ?>"></div>
<div><label class="muted">OpenAI API key (ixtiyoriy)</label><input class="input" type="password" name="openai_api_key" placeholder="Bo‘sh qoldirsangiz oldingi key saqlanadi"></div>
<div class="full"><button class="btn success" type="submit">Saqlash</button></div></form></section>
</main><?php include __DIR__.'/includes/footer.php'; ?>
