<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$users = db_read('users');
$devices = db_read('devices');
$alerts = db_read('alerts');
$services = db_read('services');
$finance = db_read('finance');

$statusStats = count_by_status($alerts);
$recentAlerts = array_slice(array_reverse($alerts), 0, 6);
$latestGeoAlert = null;
for ($i = count($alerts) - 1; $i >= 0; $i--) {
    if (isset($alerts[$i]['latitude'], $alerts[$i]['longitude'])) {
        $latestGeoAlert = $alerts[$i];
        break;
    }
}

$pageTitle = 'Dashboard';
$currentPage = 'dashboard.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main class="main-wrap">
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <section class="grid cols-4">
        <article class="card metric red"><div class="label">Jami SOS alertlar</div><div class="value" id="metric-alerts" data-counter="<?= count($alerts) ?>">0</div></article>
        <article class="card metric blue"><div class="label">Faol foydalanuvchilar</div><div class="value" id="metric-active-users" data-counter="<?= (int)($finance['active_users'] ?? count($users)) ?>">0</div></article>
        <article class="card metric purple"><div class="label">Qurilmalar sotilgan</div><div class="value" id="metric-devices-sold" data-counter="<?= (int)($finance['devices_sold'] ?? 0) ?>">0</div></article>
        <article class="card metric green"><div class="label">Xizmat turlari</div><div class="value" id="metric-services" data-counter="<?= count($services) ?>">0</div></article>
    </section>

    <section class="grid cols-2">
        <article class="card">
            <h3 style="margin-top:0;">Live incident map</h3>
            <?php if ($latestGeoAlert): ?>
                <div class="map-embed" style="margin-top:12px;"><iframe id="liveMapFrame" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q=<?= urlencode((string)$latestGeoAlert['latitude']) ?>,<?= urlencode((string)$latestGeoAlert['longitude']) ?>&z=14&output=embed"></iframe></div>
                <p class="muted" id="liveMapMeta" style="margin-top:10px;">Surxondaryo va hududiy nuqtalar · So‘nggi nuqta: <?= h((string)$latestGeoAlert['latitude']) ?>, <?= h((string)$latestGeoAlert['longitude']) ?> · Alert: <?= h((string)($latestGeoAlert['alert_id'] ?? '-')) ?></p>
            <?php else: ?>
                <p class="muted" id="liveMapMeta" style="margin-top:10px;">Lokatsiya kutilmoqda...</p>
            <?php endif; ?>
        </article>

        <article class="card">
            <div class="toolbar"><h3 style="margin:0;">So‘nggi SOS oqimi</h3><a class="btn secondary" href="alerts.php">Barchasi</a></div>
            <div class="mini-list" id="recentAlertsList" style="margin-top:12px;">
                <?php foreach ($recentAlerts as $alert): ?>
                    <div class="mini-item"><div><strong>#<?= h($alert['alert_id'] ?? '-') ?> · <?= h($alert['service'] ?? '-') ?></strong><small><?= h($alert['created_at'] ?? '-') ?> · <?= h($alert['voice_text'] ?? '') ?></small></div><span class="<?= badge_class($alert['status'] ?? 'Yangi') ?>"><?= h($alert['status'] ?? 'Yangi') ?></span></div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</main>
<script>
(function(){
const metricAlerts=document.getElementById('metric-alerts');
const metricUsers=document.getElementById('metric-active-users');
const metricDevices=document.getElementById('metric-devices-sold');
const metricServices=document.getElementById('metric-services');
const recentWrap=document.getElementById('recentAlertsList');
const liveMapFrame=document.getElementById('liveMapFrame');
const liveMapMeta=document.getElementById('liveMapMeta');
function safe(v){return String(v??'').replace(/[<>&"]/g,s=>({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[s]));}
function badgeClass(status){switch(status){case 'Yangi':return 'badge danger';case 'Qabul qilindi':return 'badge info';case 'Jarayonda':return 'badge warning';case 'Yakunlandi':return 'badge success';default:return 'badge muted';}}
async function pullLive(){try{const res=await fetch('dashboard-live.php',{cache:'no-store'});const data=await res.json();if(!data.ok)return;
if(metricAlerts)metricAlerts.textContent=Number(data.metrics.alerts||0).toLocaleString('en-US');
if(metricUsers)metricUsers.textContent=Number(data.metrics.active_users||0).toLocaleString('en-US');
if(metricDevices)metricDevices.textContent=Number(data.metrics.devices_sold||0).toLocaleString('en-US');
if(metricServices)metricServices.textContent=Number(data.metrics.services||0).toLocaleString('en-US');
if(recentWrap&&Array.isArray(data.recent_alerts)){recentWrap.innerHTML=data.recent_alerts.map((a)=>`<div class="mini-item"><div><strong>#${safe(a.alert_id)} · ${safe(a.service)}</strong><small>${safe(a.created_at)} · ${safe(a.user_name)} · ${safe(a.voice_text)}</small></div><span class="${badgeClass(a.status)}">${safe(a.status)}</span></div>`).join('');}
if(data.latest_location&&liveMapMeta){const loc=data.latest_location;const lat=safe(loc.latitude);const lng=safe(loc.longitude);liveMapMeta.textContent=`Surxondaryo va hududiy nuqtalar · So‘nggi nuqta: ${lat}, ${lng} · Alert: ${safe(loc.alert_id)}`;if(liveMapFrame){liveMapFrame.src=`https://maps.google.com/maps?q=${encodeURIComponent(String(loc.latitude))},${encodeURIComponent(String(loc.longitude))}&z=14&output=embed`;}}
}catch(e){}}
pullLive();setInterval(pullLive,4000);
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
