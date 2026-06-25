<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class CashierReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->session()->get('staff_profile_role') === 'owner_manager', 403);

        $restaurant = $request->user()->restaurant;
        abort_unless($restaurant, 403);

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $paymentMethods = $restaurant->settings['payment_methods'] ?? Order::PAYMENT_METHODS;
        $paymentMethodTotals = array_fill_keys($paymentMethods, 0);

        $cashiers = $restaurant->staffProfiles()
            ->where('role', 'cashier')
            ->orderBy('name')
            ->get()
            ->map(function ($cashier) use ($restaurant, $dateFrom, $dateTo, $paymentMethods, &$paymentMethodTotals) {
                $orders = $restaurant->orders()
                    ->where('handled_by_profile_id', $cashier->id)
                    ->whereIn('status', ['paid', 'completed'])
                    ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                    ->get();

                $methodBreakdown = array_fill_keys($paymentMethods, 0);
                foreach ($orders as $order) {
                    $method = $order->payment_method;
                    if (! $method || ! array_key_exists($method, $methodBreakdown)) {
                        continue;
                    }

                    $methodBreakdown[$method] += (float) $order->total;
                    $paymentMethodTotals[$method] += (float) $order->total;
                }

                return [
                    'cashier' => $cashier,
                    'order_count' => $orders->count(),
                    'total_revenue' => $orders->sum('total'),
                    'method_breakdown' => $methodBreakdown,
                ];
            });

        return view('restaurant.cashier-reports.index', [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'payment_methods' => $paymentMethods,
            'payment_method_totals' => $paymentMethodTotals,
            'cashiers' => $cashiers,
            'grand_total' => $cashiers->sum('total_revenue'),
            'grand_orders' => $cashiers->sum('order_count'),
        ]);
    }
}
