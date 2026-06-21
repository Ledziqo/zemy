<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('zemtab:hello', function () {
    $this->comment('ZemTab is ready.');
});

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\CleanupOldRecords;

Schedule::command(CleanupOldRecords::class)->daily();
