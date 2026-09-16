<div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-sm text-zem-muted" data-order-alerts>
    <span>{{ __('New order alerts') }}</span>
    <button type="button" class="rounded-md border border-zem-border px-3 py-2 font-bold" data-order-alert-toggle>{{ __('Enable sound alerts') }}</button>
</div>
<script>
(() => {
    const box = document.querySelector('[data-order-alerts]');
    if (!box) return;
    const toggle = box.querySelector('[data-order-alert-toggle]');
    const key = 'zemtabOrderSoundEnabled';
    let enabled = false;
    let context;
    try { enabled = localStorage.getItem(key) === '1'; } catch (_) {}

    async function unlock() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return false;
        context ||= new AudioContext();
        if (context.state === 'suspended') await context.resume();
        return context.state === 'running';
    }

    async function beep() {
        try {
            if (!await unlock()) return;
            [0, 0.4].forEach(delay => {
                const oscillator = context.createOscillator();
                const gain = context.createGain();
                const start = context.currentTime + delay;
                oscillator.frequency.value = 1200;
                gain.gain.setValueAtTime(0.001, start);
                gain.gain.exponentialRampToValueAtTime(0.25, start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, start + 0.3);
                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.onended = () => { oscillator.disconnect(); gain.disconnect(); };
                oscillator.start(start);
                oscillator.stop(start + 0.35);
            });
        } catch (_) { /* Audio must never interrupt order updates. */ }
    }

    function refreshButton() {
        toggle.textContent = enabled ? 'Sound on (click to mute)' : 'Enable sound alerts';
        toggle.setAttribute('aria-pressed', String(enabled));
    }
    window.zemtabOrderAlerts = { notify() { if (enabled) void beep(); } };
    document.addEventListener('pointerdown', () => {
        if (enabled) void unlock().catch(() => {});
    }, { once: true });
    toggle.addEventListener('click', () => {
        enabled = !enabled;
        try { localStorage.setItem(key, enabled ? '1' : '0'); } catch (_) {}
        if (enabled) void beep();
        refreshButton();
    });
    refreshButton();
})();
</script>
