<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('zemtab:hello', function () {
    $this->comment('ZemTab is ready.');
});
