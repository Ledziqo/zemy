<?php

namespace App\Support;

final class DemoProvisioning
{
    public static function migrationsAllowed(): bool
    {
        return ! app()->environment('production')
            || (app()->runningInConsole() && config('demo.allow_production_migrations', false) === true);
    }
}
