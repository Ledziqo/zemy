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
    </div>
    <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
        @foreach(['FAIL' => 'border-red-400/40 bg-red-400/10 text-red-200', 'WARN' => 'border-amber-400/40 bg-amber-400/10 text-amber-200', 'NOT RUN' => 'border-zem-border bg-zem-soft text-zem-muted', 'NOT AVAILABLE' => 'border-zem-border bg-zem-soft text-zem-muted', 'PASS' => 'border-emerald-400/40 bg-emerald-400/10 text-emerald-200', 'INFO' => 'border-blue-400/40 bg-blue-400/10 text-blue-200'] as $label => $classes)
            @if(($counts[$label] ?? 0) > 0)
                <span class="rounded-full border px-3 py-1 {{ $classes }}">{{ $label }}: {{ $counts[$label] }}</span>
            @endif
        @endforeach
    </div>
    <p class="mt-4 rounded-lg border border-amber-400/30 bg-amber-400/10 p-3 text-sm text-amber-100">{{ $report['important'] }}</p>
    <label for="release-scan-report" class="mt-5 block text-sm font-bold">Copy/paste this report back here</label>
    <textarea id="release-scan-report" readonly rows="28" class="mt-2 w-full rounded-lg border border-zem-border bg-zem-bg p-3 font-mono text-xs text-zem-cream">{{ $reportText }}</textarea>
    <p data-copy-status class="mt-2 min-h-5 text-sm text-zem-muted" aria-live="polite"></p>
    <a href="{{ route('admin.database') }}" class="mt-3 inline-block rounded-md border border-zem-border px-4 py-2 text-sm font-bold">Back to Database Maintenance</a>
</section>
<script>
    document.querySelector('[data-copy-report]')?.addEventListener('click', async () => {
        const field = document.getElementById('release-scan-report');
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
