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
    const originalTitle = document.title;

    function playDingDing() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const context = new AudioContext();

        function ding(freq, startTime) {
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = freq;
            gain.gain.setValueAtTime(0.001, context.currentTime + startTime);
            gain.gain.exponentialRampToValueAtTime(0.3, context.currentTime + startTime + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + startTime + 0.3);
            oscillator.connect(gain);
            gain.connect(context.destination);
            oscillator.start(context.currentTime + startTime);
            oscillator.stop(context.currentTime + startTime + 0.35);
        }

        // Ding-ding pattern: high ding, short pause, high ding
        ding(1200, 0);
        ding(1200, 0.4);
    }

    function startTitleFlash() {
        let toggle = false;
        clearInterval(window._titleFlashInterval);
        window._titleFlashInterval = setInterval(() => {
            document.title = toggle ? '🔔 NEW ORDER — ZemTab Work Board' : originalTitle;
            toggle = !toggle;
        }, 1000);

        // Stop flashing when tab becomes visible
        document.addEventListener('visibilitychange', function stopFlash() {
            if (!document.hidden) {
                clearInterval(window._titleFlashInterval);
                document.title = originalTitle;
                document.removeEventListener('visibilitychange', stopFlash);
            }
        });
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
        playDingDing();
        startTitleFlash();
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
            playDingDing();
        }
        refreshButton();
    });

    refreshButton();
})();
</script>
