<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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
            $this->runSetupCommands($output);

            return view('setup.show', [
                'success' => true,
                'output' => trim(implode("\n", $output)),
            ]);
        } catch (Throwable $exception) {
            if (str_contains($exception->getMessage(), "'@'127.0.0.1'")) {
                try {
                    config(['database.connections.mysql.host' => 'localhost']);
                    DB::purge('mysql');
                    $output[] = 'First database attempt used 127.0.0.1 and failed. Retrying with localhost...';
                    $this->runSetupCommands($output);

                    return view('setup.show', [
                        'success' => true,
                        'output' => trim(implode("\n", $output)),
                    ]);
                } catch (Throwable $retryException) {
                    $exception = $retryException;
                }
            }

            return view('setup.show', [
                'success' => false,
                'output' => $this->friendlyError($exception),
            ]);
        }
    }

    private function runSetupCommands(array &$output): void
    {
        Artisan::call('migrate', ['--force' => true]);
        $output[] = Artisan::output();

        Artisan::call('db:seed', ['--force' => true]);
        $output[] = Artisan::output();

        Artisan::call('optimize:clear');
        $output[] = Artisan::output();
    }

    private function friendlyError(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'Access denied for user')) {
            return $message."\n\nThis is a database login problem. In your hosting panel, confirm DB_DATABASE, DB_USERNAME, DB_PASSWORD, and DB_HOST. On Hostinger-style shared hosting, DB_HOST is usually localhost, not 127.0.0.1.";
        }

        return $message;
    }
}
