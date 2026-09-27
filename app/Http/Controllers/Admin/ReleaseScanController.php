<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ReleaseReadinessScanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReleaseScanController extends Controller
{
    public function run(Request $request, ReleaseReadinessScanner $scanner)
    {
        abort_unless($request->user()?->role === 'admin', 403);
        abort_unless((string) config('app.env') === 'staging', 403, 'The full release scan is available only on staging.');

        $lock = Cache::store('file')->lock('release-test-running', 180);
        abort_unless($lock->get(), 429, 'A release test is already running. Please wait before retrying.');
        try {
            return view('admin.release-scan', ['report' => $scanner->run()]);
        } finally {
            $lock->release();
        }
    }
}
