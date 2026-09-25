<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class CompleteReleaseTestController extends Controller
{
    public function start(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);
        abort_unless((string) config('app.env') === 'staging', 403, 'The complete release test is available only on staging.');

        $githubToken = (string) config('release_test.github_token');
        $repository = trim((string) config('release_test.github_repository'));
        abort_unless($githubToken !== '' && preg_match('/^[^\/]+\/[A-Za-z0-9_.-]+$/', $repository), 503,
            'The external release-test runner is not configured yet.');

        $runId = (string) Str::uuid();
        $state = [
            'run_id' => $runId,
            'status' => 'queued',
            'phase' => 'Queued for external runner',
            'progress' => 0,
            'started_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'report' => null,
            'error' => null,
        ];
        $this->putState($runId, $state);

        try {
            $response = Http::withToken($githubToken)
                ->acceptJson()
                ->asJson()
                ->timeout(15)
                ->post('https://api.github.com/repos/'.$repository.'/dispatches', [
                    'event_type' => (string) config('release_test.event'),
                    'client_payload' => [
                        'run_id' => $runId,
                        'base_url' => url('/'),
                        'callback_url' => route('release-test.callback', ['runId' => $runId]),
                    ],
                ]);
        } catch (Throwable $exception) {
            $state = $this->failState($state, 'Unable to reach GitHub Actions: '.class_basename($exception));
            $this->putState($runId, $state);
            abort(502, 'The external release-test runner could not be started.');
        }

        if (! $response->successful()) {
            $state = $this->failState($state, 'GitHub Actions dispatch failed with HTTP '.$response->status().'.');
            $this->putState($runId, $state);
            abort(502, 'The external release-test runner rejected the request.');
        }

        return view('admin.complete-release-test', compact('state'));
    }

    public function status(Request $request, string $runId)
    {
        abort_unless($request->user()?->role === 'admin', 403);
        $state = $this->getState($runId);

        return response()->json($state ?? [
            'run_id' => $runId,
            'status' => 'missing',
            'phase' => 'Run not found or expired',
            'progress' => 0,
        ], $state ? 200 : 404);
    }

    public function callback(Request $request, string $runId)
    {
        $expected = (string) config('release_test.callback_secret');
        $provided = (string) $request->header('X-ZemTab-Callback-Secret');
        abort_unless($expected !== '' && $provided !== '' && hash_equals($expected, $provided), 401);

        $state = $this->getState($runId);
        abort_unless($state !== null, 404);

        $status = (string) $request->input('status', $state['status']);
        abort_unless(in_array($status, ['queued', 'running', 'completed', 'failed', 'cancelled'], true), 422);

        $state['status'] = $status;
        $state['phase'] = mb_substr((string) $request->input('phase', $state['phase']), 0, 180);
        $state['progress'] = max(0, min(100, (int) $request->input('progress', $state['progress'])));
        $state['updated_at'] = now()->toIso8601String();
        if ($request->has('error')) {
            $state['error'] = mb_substr((string) $request->input('error'), 0, 1000);
        }
        if ($request->has('report')) {
            $report = $request->input('report');
            $encoded = json_encode($report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            abort_unless(is_string($encoded) && strlen($encoded) <= 2_000_000, 413, 'Release report is too large.');
            $state['report'] = $report;
        }
        if (in_array($status, ['completed', 'failed', 'cancelled'], true)) {
            $state['finished_at'] = now()->toIso8601String();
        }

        $this->putState($runId, $state);
        return response()->json(['ok' => true]);
    }

    private function key(string $runId): string
    {
        return 'complete-release-test:'.$runId;
    }

    private function getState(string $runId): ?array
    {
        $state = Cache::store('file')->get($this->key($runId));
        return is_array($state) ? $state : null;
    }

    private function putState(string $runId, array $state): void
    {
        Cache::store('file')->put($this->key($runId), $state, now()->addMinutes(max(30, (int) config('release_test.ttl_minutes', 360))));
    }

    private function failState(array $state, string $message): array
    {
        $state['status'] = 'failed';
        $state['phase'] = 'Runner start failed';
        $state['error'] = $message;
        $state['updated_at'] = now()->toIso8601String();
        return $state;
    }
}
