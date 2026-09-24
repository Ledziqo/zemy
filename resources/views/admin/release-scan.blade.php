@extends('layouts.dashboard', ['heading' => 'Full release scan', 'eyebrow' => 'Admin diagnostics'])

@section('content')
@php
    $counts = collect($report['checks'])->countBy('status');
    $statusColor = $report['overall'] === 'NO-GO' ? 'text-red-300' : ($report['overall'] === 'PASS' ? 'text-emerald-300' : 'text-amber-200');
    $reportText = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
@endphp
<section class="max-w-5xl rounded-xl border border-zem-border bg-zem-card p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold">Result: <span class="{{ $statusColor }}">{{ $report['overall'] }}</span></h2>
            <p class="mt-1 text-sm text-zem-muted">{{ $report['generated_at'] }} · {{ $report['duration_ms'] }} ms · {{ count($report['checks']) }} checks</p>
        </div>
        <button type="button" data-copy-report class="rounded-md bg-zem-gold px-4 py-2 font-bold text-white">Copy full report</button>
        <button type="button" data-download-report class="rounded-md border border-zem-border px-4 py-2 font-bold">Download report</button>
    </div>
    <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
        @foreach(['FAIL' => 'border-red-400/40 bg-red-400/10 text-red-200', 'WARN' => 'border-amber-400/40 bg-amber-400/10 text-amber-200', 'NOT RUN' => 'border-zem-border bg-zem-soft text-zem-muted', 'NOT AVAILABLE' => 'border-zem-border bg-zem-soft text-zem-muted', 'PASS' => 'border-emerald-400/40 bg-emerald-400/10 text-emerald-200', 'INFO' => 'border-blue-400/40 bg-blue-400/10 text-blue-200'] as $label => $classes)
            @if(($counts[$label] ?? 0) > 0)
                <span class="rounded-full border px-3 py-1 {{ $classes }}">{{ $label }}: {{ $counts[$label] }}</span>
            @endif
        @endforeach
    </div>
    <p class="mt-4 rounded-lg border border-amber-400/30 bg-amber-400/10 p-3 text-sm text-amber-100">{{ $report['important'] }}</p>
    <div class="mt-4 rounded-lg border border-blue-400/30 bg-blue-400/10 p-3 text-sm text-blue-100" data-browser-status aria-live="polite">Browser smoke checks are starting…</div>
    <label for="release-scan-report" class="mt-5 block text-sm font-bold">Copy/paste this report back here</label>
    <textarea id="release-scan-report" readonly rows="28" class="mt-2 w-full rounded-lg border border-zem-border bg-zem-bg p-3 font-mono text-xs text-zem-cream">{{ $reportText }}</textarea>
    <p data-copy-status class="mt-2 min-h-5 text-sm text-zem-muted" aria-live="polite"></p>
    <a href="{{ route('admin.database') }}" class="mt-3 inline-block rounded-md border border-zem-border px-4 py-2 text-sm font-bold">Back to Database Maintenance</a>
</section>
<script>
    const releaseReport = @json($report);
    const reportField = document.getElementById('release-scan-report');
    const browserStatus = document.querySelector('[data-browser-status]');
    const browserTargets = (releaseReport.inventory?.routes || [])
        .filter(route => route.browser_url && route.method.includes('GET'))
        .slice(0, 30);
    const runBrowserSmoke = async () => {
        const results = [];
        for (const target of browserTargets) {
            const started = performance.now();
            try {
                const response = await fetch(target.browser_url, {
                    method: 'GET', credentials: 'same-origin', cache: 'no-store',
                    headers: { 'X-Release-Browser-Smoke': '1', 'Accept': 'text/html,application/json' },
                    signal: AbortSignal.timeout(10000),
                });
                const expectedDenied = target.name.startsWith('restaurant.') && response.status === 403;
                results.push({name: target.name, uri: target.uri, status: response.status, ok: response.ok || expectedDenied, expected: expectedDenied, duration_ms: Math.round((performance.now() - started) * 100) / 100});
            } catch (error) {
                results.push({name: target.name, uri: target.uri, status: null, ok: false, duration_ms: Math.round((performance.now() - started) * 100) / 100, error: error.name || 'fetch_failed'});
            }
        }
        const failures = results.filter(result => !result.ok);
        const expectedDenied = results.filter(result => result.expected).length;
        releaseReport.browser_smoke = {
            mode: 'same-origin parameterless GET smoke test in the authenticated admin browser',
            target_count: results.length,
            passed: results.length - failures.length,
            failed: failures.length,
            expected_denied: expectedDenied,
            results,
            limitations: ['Does not click buttons or submit mutations.', 'Does not test routes requiring path parameters.', 'This is browser smoke coverage, not a full end-to-end certification.'],
        };
        reportField.value = JSON.stringify(releaseReport, null, 2);
        browserStatus.textContent = `Browser smoke complete: ${results.length - failures.length} passed (${expectedDenied} expected role denials), ${failures.length} failed. Mutation and parameterized workflows remain separately reported.`;
        browserStatus.className = `mt-4 rounded-lg border p-3 text-sm ${failures.length ? 'border-red-400/30 bg-red-400/10 text-red-100' : 'border-emerald-400/30 bg-emerald-400/10 text-emerald-100'}`;
    };
    runBrowserSmoke();

    document.querySelector('[data-download-report]')?.addEventListener('click', () => {
        const url = URL.createObjectURL(new Blob([reportField.value], {type: 'application/json'}));
        const link = document.createElement('a');
        link.href = url; link.download = 'zemtab-release-test.json'; link.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    });
    document.querySelector('[data-copy-report]')?.addEventListener('click', async () => {
        const field = reportField;
        const status = document.querySelector('[data-copy-status]');
        try {
            await navigator.clipboard.writeText(field.value);
            status.textContent = 'Full report copied. Paste it into your Codex chat.';
        } catch (_) {
            field.focus(); field.select();
            status.textContent = 'Clipboard access was blocked. The report is selected; copy it with Ctrl+C.';
        }
    });
</script>
@endsection
