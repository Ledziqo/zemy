<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ServiceRequest;
use App\Models\GuestSession;
use Illuminate\Console\Command;

class CleanupOldRecords extends Command
{
    protected $signature = 'cleanup:old-records';
    protected $description = 'Delete orders and service requests older than 30 days';

    public function handle(): void
    {
        $cutoff = now()->subDays(30);

        $orderCount = 0;
        Order::where('created_at', '<', $cutoff)
            ->select('id')
            ->chunkById(200, function ($orders) use (&$orderCount) {
                foreach ($orders as $order) {
                    $order->items()->delete();
                    $order->delete();
                    $orderCount++;
                }
            });

        // Delete old service requests
        $requestCount = ServiceRequest::where('created_at', '<', $cutoff)->delete();
        $sessionCount = GuestSession::where('expires_at', '<', now()->subDay())
            ->whereDoesntHave('orders')
            ->whereDoesntHave('serviceRequests')
            ->whereDoesntHave('payments')
            ->delete();

        $this->info("Deleted {$orderCount} old orders, {$requestCount} old service requests, and {$sessionCount} expired guest sessions.");
    }
}
