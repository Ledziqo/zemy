@extends('layouts.dashboard', ['heading' => 'Complete release test', 'eyebrow' => 'Staging test runner'])

@section('content')
<section class="max-w-4xl rounded-xl border border-zem-border bg-zem-card p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold">Complete release test is running</h2>
            <p class="mt-1 text-sm text-zem-muted">The external runner will report progress here. Keep this page open until cleanup is complete.</p>
        </div>
        <span data-status class="rounded-full border border-amber-400/40 bg-amber-400/10 px-3 py-1 text-sm font-bold text-amber-100">Queued</span>
    </div>
    <div class="mt-5 h-3 overflow-hidden rounded-full bg-zem-bg">
        <div data-progress class="h-full w-0 bg-zem-gold transition-all" style="width: 0%"></div>
    </div>
    <p data-phase class="mt-3 text-sm text-zem-muted">Queued for external runner</p>
    <pre data-error class="mt-4 hidden whitespace-pre-wrap rounded-lg border border-red-400/40 bg-red-400/10 p-3 text-sm text-red-100"></pre>
    <textarea data-report readonly rows="24" class="mt-5 hidden w-full rounded-lg border border-zem-border bg-zem-bg p-3 font-mono text-xs text-zem-cream"></textarea>
    <a href="{{ route('admin.database') }}" class="mt-4 inline-block rounded-md border border-zem-border px-4 py-2 text-sm font-bold">Back to Database Maintenance</a>
</section>
<script>
    const statusUrl = @json(route('admin.database.complete-test.status', ['runId' => $state['run_id']]));
    const statusEl = document.querySelector('[data-status]');
    const phaseEl = document.querySelector('[data-phase]');
    const progressEl = document.querySelector('[data-progress]');
    const errorEl = document.querySelector('[data-error]');
    const reportEl = document.querySelector('[data-report]');
    const poll = async () => {
        try {
            const response = await fetch(statusUrl, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
            const state = await response.json();
            statusEl.textContent = String(state.status || 'unknown').toUpperCase();
            phaseEl.textContent = state.phase || '';
            const progress = Math.max(0, Math.min(100, Number(state.progress || 0)));
            progressEl.style.width = progress + '%';
            if (state.error) { errorEl.textContent = state.error; errorEl.classList.remove('hidden'); }
            if (state.report) {
                reportEl.value = JSON.stringify(state.report, null, 2);
                reportEl.classList.remove('hidden');
            }
            if (!['completed', 'failed', 'cancelled', 'missing'].includes(state.status)) setTimeout(poll, 3000);
        } catch (_) {
            phaseEl.textContent = 'Waiting for the runner connection…';
            setTimeout(poll, 5000);
        }
    };
    poll();
</script>
@endsection
