<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class SetupController extends Controller
{
    public function show()
    {
        return view('setup.show');
    }

    public function run(Request $request)
    {
        $output = [];

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output[] = Artisan::output();

            Artisan::call('db:seed', ['--force' => true]);
            $output[] = Artisan::output();

            Artisan::call('optimize:clear');
            $output[] = Artisan::output();

            return view('setup.show', [
                'success' => true,
                'output' => trim(implode("\n", $output)),
            ]);
        } catch (Throwable $exception) {
            return view('setup.show', [
                'success' => false,
                'output' => $exception->getMessage(),
            ]);
        }
    }
}
