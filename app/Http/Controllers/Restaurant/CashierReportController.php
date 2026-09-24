<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\ReportCache;
use App\Support\WorkBoardRevision;
use Illuminate\Http\Request;

class CashierReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->session()->get('staff_profile_role') === 'owner_manager', 403);

        $restaurant = $request->user()->restaurant;
        abort_unless($restaurant, 403);

        $filters = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();
        abort_if($dateTo < $dateFrom, 422, 'End date must be on or after start date.');

        $paymentMethodsSetting = $restaurant->settings['payment_methods'] ?? Order::PAYMENT_METHODS;
        $revision = WorkBoardRevision::current((int) $restaurant->id);
        $payload = ReportCache::remember('cashier', (int) $restaurant->id, $revision, [
            'from' => $dateFrom,
            'to' => $dateTo,
            'methods' => $paymentMethodsSetting,
            'hotel' => $restaurant->isHotel(),
        ], function () use ($restaurant, $dateFrom, $dateTo, $paymentMethodsSetting): array {
            // One aggregate query, regardless of how many staff profiles handled orders.
            $totals = $restaurant->orders()
                ->whereIn('status', ['paid', 'completed'])
                ->where('created_at', '>=', $dateFrom.' 00:00:00')
                ->where('created_at', '<', \Carbon\Carbon::parse($dateTo)->addDay()->toDateString().' 00:00:00')
                ->selectRaw('handled_by_profile_id, payment_method, COUNT(*) AS order_count, SUM(total) AS revenue')
                ->groupBy('handled_by_profile_id', 'payment_method')
                ->get();
            $paymentMethods = $paymentMethodsSetting;
            if (! in_array('credit', $paymentMethods, true)) {
                $paymentMethods[] = 'credit';
            }
            if ($restaurant->isHotel() && ! in_array('room_credit', $paymentMethods, true)) {
                $paymentMethods[] = 'room_credit';
            }
            $paymentMethods = array_values(array_unique(array_merge($paymentMethods,
                $totals->map(fn ($row) => $row->payment_method ?: 'unspecified')->all())));
            $paymentMethodTotals = array_fill_keys($paymentMethods, 0);

            $cashiers = $restaurant->staffProfiles()
                ->orderBy('name')
                ->get();
            $profiles = $cashiers->keyBy('id');
            $rows = [];
            foreach ($cashiers as $profile) {
                if (in_array($profile->role, ['cashier', 'owner_manager'], true)) {
                    $rows[$profile->id] = ['cashier' => $profile, 'order_count' => 0, 'total_revenue' => 0, 'method_breakdown' => array_fill_keys($paymentMethods, 0)];
                }
            }
            foreach ($totals as $total) {
                $profile = $profiles->get($total->handled_by_profile_id);
                $key = $profile?->id ?? 'unassigned';
                $rows[$key] ??= ['cashier' => $profile, 'order_count' => 0, 'total_revenue' => 0, 'method_breakdown' => array_fill_keys($paymentMethods, 0)];
                $method = $total->payment_method ?: 'unspecified';
                $rows[$key]['order_count'] += (int) $total->order_count;
                $rows[$key]['total_revenue'] += (float) $total->revenue;
                $rows[$key]['method_breakdown'][$method] += (float) $total->revenue;
                $paymentMethodTotals[$method] += (float) $total->revenue;
            }

            return [
                'payment_methods' => $paymentMethods,
                'payment_method_totals' => $paymentMethodTotals,
                'cashiers' => array_values($rows),
            ];
        });

        return view('restaurant.cashier-reports.index', [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'payment_methods' => $payload['payment_methods'],
            'payment_method_totals' => $payload['payment_method_totals'],
            'cashiers' => collect($payload['cashiers']),
            'grand_total' => collect($payload['cashiers'])->sum('total_revenue'),
            'grand_orders' => collect($payload['cashiers'])->sum('order_count'),
        ]);
    }
}
