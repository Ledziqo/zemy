<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PaymentController extends Controller
{
    public function index()
    {
        $restaurants = Restaurant::with(['subscriptions' => function ($q) { $q->latest('starts_at'); }])->orderBy('name')->get();

        $summary = [
            'total' => $restaurants->count(),
            'active' => 0,
            'expiring' => 0,
            'expired' => 0,
            'unpaid' => 0,
        ];

        $rows = $restaurants->map(function ($restaurant) use (&$summary) {
            $sub = $restaurant->latestSubscription();
            $daysLeft = $sub && $sub->ends_at ? (int) now()->startOfDay()->diffInDays($sub->ends_at, false) : null;

            if (! $sub) {
                return (object) [
                    'restaurant' => $restaurant, 'subscription' => null, 'daysLeft' => null,
                    'statusLabel' => 'No subscription', 'statusColor' => 'border-zem-border text-zem-muted',
                ];
            }

            $isExpired = $daysLeft !== null && $daysLeft < 0;
            $isExpiring = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 3;

            if ($sub->status === 'unpaid') { $summary['unpaid']++; }
            elseif ($isExpired) { $summary['expired']++; }
            elseif ($isExpiring) { $summary['expiring']++; }
            else { $summary['active']++; }

            $color = $isExpired ? 'bg-red-100 text-red-700 border-red-300'
                : ($isExpiring ? 'bg-zem-gold/20 text-zem-gold border-zem-gold/40'
                : ($sub->status === 'unpaid' ? 'bg-red-100 text-red-700 border-red-300'
                : 'bg-green-100 text-green-700 border-green-300'));

            $label = $isExpired ? 'Expired' : ($isExpiring ? $daysLeft . ' days left' : ($sub->status === 'unpaid' ? 'Unpaid' : 'Active'));

            return (object) [
                'restaurant' => $restaurant, 'subscription' => $sub, 'daysLeft' => $daysLeft,
                'statusLabel' => $label, 'statusColor' => $color,
            ];
        });

        return view('admin.payments.index', ['rows' => $rows, 'summary' => $summary]);
    }

    public function markPaid(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'payment_method' => ['nullable', 'string', 'max:255'],
            'extend_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $extendDays = (int) ($data['extend_days'] ?? 30);
        $baseDate = now();
        if ($subscription->ends_at) {
            try {
                $endDate = \Carbon\Carbon::parse($subscription->ends_at);
                if ($endDate->greaterThan(now())) {
                    $baseDate = $endDate;
                }
            } catch (\Exception $e) {
                // If parsing fails, use now()
            }
        }

        $subscription->update([
            'status' => 'active',
            'ends_at' => $baseDate->addDays($extendDays)->toDateString(),
            'payment_method' => $data['payment_method'] ?? $subscription->payment_method,
        ]);

        if (Schema::hasColumn('restaurants', 'dashboard_access_status')) {
            $subscription->restaurant->update(['dashboard_access_status' => 'active']);
        }

        return back()->with('success', 'Subscription marked as paid. Extended by ' . $extendDays . ' days.');
    }
}
