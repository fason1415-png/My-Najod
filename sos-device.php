<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$users = db_read('users');
$devices = db_read('devices');
$pageTitle = 'Bemor SOS demo';
$currentPage = 'sos-device.php';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main class="main-wrap">
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <section class="grid cols-2">
        <article class="card alert-sos" style="text-align:center;">
            <h3 style="margin-top:0;">SOS qurilma emulyatori</h3>
            <p class="muted">Bemor SOS bosadi, shikoyat aytadi, AI tahlil qiladi va xizmatga yo‘naltiradi.</p>
            <div style="display:flex;justify-content:center;padding:10px 0 20px;"><button type="button" id="sosTriggerBtn" class="sos-button">SOS</button></div>
            <p id="recordStatus" class="muted">Holat: kutilyapti</p>
        </article>

        <article class="card">
            <h3 style="margin-top:0;">Shikoyat yuborish</h3>
            <div class="form-grid">
                <div><label class="muted">Bemor</label><select id="userSelect"><?php foreach ($users as $u): ?><option value="<?= h((string)$u['id']) ?>"><?= h(($u['id'] ?? '') . ' - ' . ($u['full_name'] ?? '')) ?></option><?php endforeach; ?></select></div>
                <div><label class="muted">Qurilma</label><select id="deviceSelect"><?php foreach ($devices as $d): ?><option value="<?= h((string)$d['device_id']) ?>"><?= h(($d['device_id'] ?? '') . ' - ' . ($d['device_name'] ?? '')) ?></option><?php endforeach; ?></select></div>
                <div class="full"><label class="muted">Voice text (shikoyat)</label><textarea id="voiceText" placeholder="Masalan: Yuragim siqilyapti, nafas olishim qiyin..."></textarea></div>
            </div>
            <div class="actions" style="margin-top:12px;"><button class="btn primary" type="button" id="startVoiceBtn">🎤 Ovozdan matn olish</button><button class="btn danger" type="button" id="sendSosBtn">SOS yuborish</button></div>
        </article>
    </section>

    <section class="card" id="sosResultCard" style="display:none;">
        <h3 style="margin-top:0;">AI natija va yo‘naltirish</h3>
        <div class="mini-list" id="sosResultList"></div>
    </section>
</main>
<script>
(function () {
const sosTriggerBtn=document.getElementById('sosTriggerBtn');
const startVoiceBtn=document.getElementById('startVoiceBtn');
const sendSosBtn=document.getElementById('sendSosBtn');
const voiceText=document.getElementById('voiceText');
const userSelect=document.getElementById('userSelect');
const deviceSelect=document.getElementById('deviceSelect');
const recordStatus=document.getElementById('recordStatus');
const resultCard=document.getElementById('sosResultCard');
const resultList=document.getElementById('sosResultList');
function safeText(v){return String(v??'').replace(/[<>&"]/g,s=>({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[s]));}
async function sendSos(){
const payload={user_id:userSelect.value,device_id:deviceSelect.value,voice_text:voiceText.value.trim()};
if(!payload.voice_text){recordStatus.textContent='Holat: avval shikoyat matnini kiriting.';return;}
sendSosBtn.disabled=true;sendSosBtn.textContent='Yuborilmoqda...';recordStatus.textContent='Holat: AI tahlil qilmoqda...';
try{const response=await fetch('sos-create.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});const data=await response.json();if(!data.ok){throw new Error(data.message||'Xatolik');}
const a=data.alert;resultList.innerHTML=`<div class="mini-item"><strong>Alert ID</strong><span>${safeText(a.alert_id)}</span></div><div class="mini-item"><strong>AI natija</strong><span>${safeText(a.ai_result)}</span></div><div class="mini-item"><strong>Xizmat</strong><span>${safeText(a.service)} ${a.service_hotline?'('+safeText(a.service_hotline)+')':''}</span></div>`;resultCard.style.display='block';recordStatus.textContent='Holat: SOS qabul qilindi.';voiceText.value='';}
catch(error){recordStatus.textContent='Holat: yuborishda xatolik - '+error.message;}
finally{sendSosBtn.disabled=false;sendSosBtn.textContent='SOS yuborish';}}
function startVoiceRecognition(){const Recognition=window.SpeechRecognition||window.webkitSpeechRecognition;if(!Recognition){recordStatus.textContent='Holat: brauzer speech tanimaydi.';return;}const rec=new Recognition();rec.lang='uz-UZ';rec.interimResults=false;rec.maxAlternatives=1;recordStatus.textContent='Holat: tinglanmoqda...';rec.start();rec.onresult=(e)=>{voiceText.value=(e.results[0][0].transcript||'').trim();recordStatus.textContent='Holat: ovoz matnga o‘girildi.';};rec.onerror=()=>{recordStatus.textContent='Holat: ovoz aniqlashda xatolik.';};}
startVoiceBtn.addEventListener('click',startVoiceRecognition);sendSosBtn.addEventListener('click',sendSos);sosTriggerBtn.addEventListener('click',()=>{sosTriggerBtn.classList.add('pulse');sendSos();});
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
