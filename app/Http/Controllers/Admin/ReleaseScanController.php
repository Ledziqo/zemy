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

        $lock = Cache::store('file')->lock('release-test-running', 180);
        abort_unless($lock->get(), 429, 'A release test is already running. Please wait before retrying.');
        try {
            return view('admin.release-scan', ['report' => $scanner->run()]);
        } finally {
            $lock->release();
        }
    }
}
