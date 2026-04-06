document.addEventListener('DOMContentLoaded', () => {
  animateCounters();
  initSosAlarmMonitor();
});

function animateCounters() {
  document.querySelectorAll('[data-counter]').forEach((el) => {
    const target = Number(el.dataset.counter || '0');
    const duration = 1200;
    const start = performance.now();
    const tick = (now) => {
      const progress = Math.min(1, (now - start) / duration);
      const value = Math.floor(target * (1 - Math.pow(1 - progress, 3)));
      el.textContent = value.toLocaleString('en-US');
      if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  });
}

function initSosAlarmMonitor() {
  if (!document.body || document.body.dataset.auth !== '1') return;

  const state = {
    pendingCount: 0,
    latestAlertId: '',
    isMuted: localStorage.getItem('sos_alarm_muted') === '1',
    isPlaying: false,
    widget: null,
    audioCtx: null,
    oscA: null,
    oscB: null,
    gain: null,
    sweepTimer: null,
  };

  state.widget = buildAlarmWidget(state);

  async function poll() {
    try {
      const res = await fetch('alert-sound-state.php', { cache: 'no-store' });
      const data = await res.json();
      if (!data.ok) return;

      const pending = Number(data.pending_count || 0);
      const latest = data.latest_pending || null;
      const latestId = latest && latest.alert_id ? String(latest.alert_id) : '';

      state.pendingCount = pending;
      if (latestId) state.latestAlertId = latestId;
      refreshAlarmWidget(state, latest);

      if (pending > 0 && !state.isMuted) startSiren(state);
      else stopSiren(state);
    } catch (_) {}
  }

  const unlock = () => {
    ensureAudioContext(state);
    if (state.audioCtx && state.audioCtx.state === 'suspended') {
      state.audioCtx.resume().catch(() => {});
    }
    if (state.pendingCount > 0 && !state.isMuted) startSiren(state);
  };
  window.addEventListener('pointerdown', unlock, { passive: true });
  window.addEventListener('keydown', unlock, { passive: true });

  poll();
  setInterval(poll, 3000);
}

function buildAlarmWidget(state) {
  const wrap = document.createElement('div');
  wrap.className = 'sos-alarm-widget';
  wrap.innerHTML = `<div class="sos-alarm-title">SOS Sirena</div><div class="sos-alarm-sub" id="sosAlarmSub">Holat: kutilyapti</div><button class="btn secondary sos-alarm-btn" id="sosAlarmBtn" type="button">${state.isMuted ? "Ovoz o'chirilgan" : 'Ovoz yoqilgan'}</button>`;
  document.body.appendChild(wrap);

  const btn = wrap.querySelector('#sosAlarmBtn');
  btn?.addEventListener('click', () => {
    state.isMuted = !state.isMuted;
    localStorage.setItem('sos_alarm_muted', state.isMuted ? '1' : '0');
    btn.textContent = state.isMuted ? "Ovoz o'chirilgan" : 'Ovoz yoqilgan';
    if (state.isMuted) stopSiren(state);
    else if (state.pendingCount > 0) startSiren(state);
    wrap.classList.toggle('muted', state.isMuted);
  });

  wrap.classList.toggle('muted', state.isMuted);
  return wrap;
}

function refreshAlarmWidget(state, latest) {
  if (!state.widget) return;
  const sub = state.widget.querySelector('#sosAlarmSub');
  if (!sub) return;
  if (state.pendingCount > 0) {
    const detail = latest ? `#${latest.alert_id} · ${latest.service || 'SOS'}` : 'Yangi SOS mavjud';
    sub.innerHTML = `Holat: <strong>${state.pendingCount}</strong> ta yangi · ${detail}`;
    state.widget.classList.add('active');
  } else {
    sub.textContent = "Holat: yangi SOS yo'q";
    state.widget.classList.remove('active');
  }
}

function ensureAudioContext(state) {
  if (state.audioCtx) return;
  const AC = window.AudioContext || window.webkitAudioContext;
  if (!AC) return;
  state.audioCtx = new AC();
}

function startSiren(state) {
  ensureAudioContext(state);
  if (!state.audioCtx || state.isPlaying) return;
  if (state.audioCtx.state === 'suspended') state.audioCtx.resume().catch(() => {});

  state.oscA = state.audioCtx.createOscillator();
  state.oscB = state.audioCtx.createOscillator();
  state.gain = state.audioCtx.createGain();

  state.oscA.type = 'sawtooth';
  state.oscB.type = 'triangle';
  state.gain.gain.value = 0.0001;

  state.oscA.connect(state.gain);
  state.oscB.connect(state.gain);
  state.gain.connect(state.audioCtx.destination);

  state.oscA.start();
  state.oscB.start();
  state.isPlaying = true;

  let hi = false;
  const sweep = () => {
    if (!state.audioCtx || !state.gain || !state.oscA || !state.oscB) return;
    const now = state.audioCtx.currentTime;
    const fA = hi ? 980 : 650;
    const fB = hi ? 1180 : 820;
    state.oscA.frequency.cancelScheduledValues(now);
    state.oscB.frequency.cancelScheduledValues(now);
    state.gain.gain.cancelScheduledValues(now);
    state.oscA.frequency.linearRampToValueAtTime(fA, now + 0.8);
    state.oscB.frequency.linearRampToValueAtTime(fB, now + 0.8);
    state.gain.gain.setValueAtTime(0.0001, now);
    state.gain.gain.exponentialRampToValueAtTime(0.12, now + 0.07);
    state.gain.gain.exponentialRampToValueAtTime(0.035, now + 0.72);
    hi = !hi;
  };

  sweep();
  state.sweepTimer = setInterval(sweep, 850);
}

function stopSiren(state) {
  if (!state.isPlaying) return;
  if (state.sweepTimer) {
    clearInterval(state.sweepTimer);
    state.sweepTimer = null;
  }
  try {
    if (state.gain && state.audioCtx) {
      const now = state.audioCtx.currentTime;
      state.gain.gain.cancelScheduledValues(now);
      state.gain.gain.setValueAtTime(state.gain.gain.value, now);
      state.gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.25);
    }
  } catch (_) {}
  setTimeout(() => {
    try { state.oscA && state.oscA.stop(); state.oscB && state.oscB.stop(); } catch (_) {}
    state.oscA = null; state.oscB = null; state.gain = null;
  }, 300);
  state.isPlaying = false;
}
