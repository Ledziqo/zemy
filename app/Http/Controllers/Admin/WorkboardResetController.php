<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuestSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\ServiceRequest;
use App\Support\WorkBoardRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkboardResetController extends Controller
{
    public function preview(Request $request)
    {
        $data = $request->validate(['restaurant_id' => ['required', 'integer', 'exists:restaurants,id']]);
        $restaurant = Restaurant::findOrFail($data['restaurant_id']);

        return view('admin.workboard-reset-preview', [
            'restaurant' => $restaurant,
            'counts' => $this->counts($restaurant),
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'restaurant_id' => ['required', 'integer', 'exists:restaurants,id'],
            'confirmation' => ['required', 'string', 'max:120'],
            'paused' => ['accepted'],
        ]);
        $restaurant = Restaurant::findOrFail($data['restaurant_id']);
        if (! hash_equals('RESET '.$restaurant->slug, $data['confirmation'])) {
            return redirect('/admin/database/workboard-reset/preview?restaurant_id='.$restaurant->id)
                ->withErrors(['confirmation' => 'The confirmation phrase does not match this venue.'])
                ->withInput();
        }

        $deleted = DB::transaction(function () use ($restaurant) {
            // Serialize the reset against new rows using this venue's foreign key.
            Restaurant::whereKey($restaurant->id)->lockForUpdate()->firstOrFail();
            $counts = $this->counts($restaurant);
            $orderIds = Order::where('restaurant_id', $restaurant->id)->select('id');
            $sessionIds = GuestSession::where('restaurant_id', $restaurant->id)->select('id');

            $deleted = [
                'order_items' => OrderItem::whereIn('order_id', clone $orderIds)->delete(),
                'payments' => Payment::where('restaurant_id', $restaurant->id)
                    ->where(function ($query) use ($orderIds, $sessionIds) {
                        $query->whereIn('order_id', clone $orderIds)
                            ->orWhereIn('guest_session_id', clone $sessionIds);
                    })->delete(),
                'service_requests' => ServiceRequest::where('restaurant_id', $restaurant->id)->delete(),
                'orders' => Order::where('restaurant_id', $restaurant->id)->delete(),
                'guest_sessions' => GuestSession::where('restaurant_id', $restaurant->id)->delete(),
            ];

            return ['counts' => $counts, 'deleted' => $deleted];
        }, 3);

        WorkBoardRevision::bump((int) $restaurant->id);

        return redirect('/admin/database')->with('workboard_reset_result', [
            'restaurant' => $restaurant->name,
            'deleted' => $deleted['deleted'],
            'preview' => $deleted['counts'],
        ]);
    }

    private function counts(Restaurant $restaurant): array
    {
        $orderIds = Order::where('restaurant_id', $restaurant->id)->select('id');
        $sessionIds = GuestSession::where('restaurant_id', $restaurant->id)->select('id');
        $payments = Payment::where('restaurant_id', $restaurant->id)
            ->where(fn ($query) => $query->whereIn('order_id', clone $orderIds)
                ->orWhereIn('guest_session_id', clone $sessionIds));

        return [
            'orders' => Order::where('restaurant_id', $restaurant->id)->count(),
            'order_items' => OrderItem::whereIn('order_id', clone $orderIds)->count(),
            'payments' => (clone $payments)->count(),
            'service_requests' => ServiceRequest::where('restaurant_id', $restaurant->id)->count(),
            'guest_sessions' => GuestSession::where('restaurant_id', $restaurant->id)->count(),
            'due_credits' => Order::where('restaurant_id', $restaurant->id)
                ->where('status', 'completed')->where('payment_status', '!=', 'paid')
                ->whereIn('payment_method', ['credit', 'room_credit'])->count(),
            'due_credit_total' => (float) Order::where('restaurant_id', $restaurant->id)
                ->where('status', 'completed')->where('payment_status', '!=', 'paid')
                ->whereIn('payment_method', ['credit', 'room_credit'])->sum('total'),
        ];
    }
}
