<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) return;

        DB::table('subscriptions')->orderBy('id')->chunkById(200, function ($subscriptions) {
            foreach ($subscriptions as $subscription) {
                if ($subscription->ends_at) {
                    $due = Carbon::parse($subscription->ends_at)->startOfDay();
                    if ($due->day !== 1) $due->startOfMonth()->addMonth();
                } else {
                    $start = $subscription->starts_at ? Carbon::parse($subscription->starts_at) : today();
                    $due = ($start->greaterThan(today()) ? $start : today())->copy()->startOfMonth()->addMonth();
                }
                DB::table('subscriptions')->where('id', $subscription->id)->update(['ends_at' => $due->toDateString()]);
            }
        });
    }

    public function down(): void
    {
        // Forward-only date alignment: the original arbitrary dates cannot be inferred.
        // No subscriptions, prices, payments or access overrides are removed.
    }
};
