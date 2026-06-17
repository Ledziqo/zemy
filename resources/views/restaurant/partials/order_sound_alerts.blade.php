<div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-md border border-zem-border bg-zem-card px-4 py-3 text-sm text-zem-muted" data-order-alerts data-latest-order-id="{{ $latestOrderId ?? 0 }}">
    <span>Sound alerts for new orders</span>
    <button type="button" class="rounded-md border border-zem-border bg-zem-bg px-3 py-2 text-sm font-bold text-zem-cream transition hover:border-zem-gold hover:text-zem-gold" data-order-alert-toggle>Enable sound alerts</button>
</div>
<script>
(() => {
    const box = document.querySelector('[data-order-alerts]');
    if (!box) return;

    const keyEnabled = 'zemtabOrderSoundEnabled';
    const keyLatest = 'zemtabLatestOrderId';
    const latest = Number(box.dataset.latestOrderId || 0);
    const toggle = box.querySelector('[data-order-alert-toggle]');

    function playBeep() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const context = new AudioContext();
        const oscillator = context.createOscillator();
        const gain = context.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = 880;
        gain.gain.setValueAtTime(0.001, context.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.22, context.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.45);
        oscillator.connect(gain);
        gain.connect(context.destination);
        oscillator.start();
        oscillator.stop(context.currentTime + 0.5);
    }

    function refreshButton() {
        const enabled = localStorage.getItem(keyEnabled) === '1';
        toggle.textContent = enabled ? 'Sound alerts enabled' : 'Enable sound alerts';
        toggle.classList.toggle('bg-zem-gold', enabled);
        toggle.classList.toggle('bg-zem-bg', !enabled);
        toggle.classList.toggle('border-zem-gold', enabled);
        toggle.classList.toggle('text-white', enabled);
        toggle.classList.toggle('text-zem-cream', !enabled);
    }

    const previous = Number(localStorage.getItem(keyLatest) || 0);
    const enabled = localStorage.getItem(keyEnabled) === '1';
    if (enabled && previous > 0 && latest > previous) {
        playBeep();
    }
    if (latest > 0) {
        localStorage.setItem(keyLatest, String(latest));
    }

    toggle.addEventListener('click', () => {
        const nextEnabled = localStorage.getItem(keyEnabled) !== '1';
        localStorage.setItem(keyEnabled, nextEnabled ? '1' : '0');
        if (latest > 0) {
            localStorage.setItem(keyLatest, String(latest));
        }
        if (nextEnabled) {
            playBeep();
        }
        refreshButton();
    });

    refreshButton();
})();
</script>

