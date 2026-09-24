<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ReleaseReadinessScanner;
use Illuminate\Http\Request;

class ReleaseScanController extends Controller
{
    public function run(Request $request, ReleaseReadinessScanner $scanner)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        return view('admin.release-scan', [
            'report' => $scanner->run(),
        ]);
    }
}
