<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ServiceRequest;
use Illuminate\Console\Command;

class CleanupOldRecords extends Command
{
    protected $signature = 'cleanup:old-records';
    protected $description = 'Delete orders and service requests older than 30 days';

    public function handle(): void
    {
        $cutoff = now()->subDays(30);

        // Delete old orders (this will cascade to order_items if the DB has foreign keys,
        // otherwise we delete them explicitly)
        $orders = Order::where('created_at', '<', $cutoff)->get();
        $orderCount = $orders->count();

        foreach ($orders as $order) {
            $order->items()->delete();
            $order->delete();
        }

        // Delete old service requests
        $requestCount = ServiceRequest::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$orderCount} old orders and {$requestCount} old service requests (older than 30 days).");
    }
}
