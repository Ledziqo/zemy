<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Restaurant;
use App\Support\GuestVisitManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentProofController extends Controller
{
    public function store(Request $request, GuestVisitManager $visits, string $restaurant_slug, string $table_number)
    {
        $restaurant = Restaurant::where('slug', $restaurant_slug)->where('is_active', true)->firstOrFail();
        $restaurantTable = $restaurant->tables()->where('table_number', $table_number)->where('is_active', true)->firstOrFail();
        $visit = $visits->resolve($request, $restaurant, $restaurantTable);

        $data = $request->validate([
            'method' => ['required', Rule::in(['telebirr', 'cbe'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'proof' => ['required', 'image', 'max:4096'],
        ]);

        $directory = public_path('uploads/payment-proofs');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = $request->file('proof');
        $filename = $visit->id.'-'.now()->format('YmdHis').'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        Payment::create([
            'restaurant_id' => $restaurant->id,
            'guest_session_id' => $visit->id,
            'amount' => $visit->orders()->whereNotIn('status', ['cancelled'])->sum('total'),
            'method' => $data['method'],
            'status' => 'pending',
            'reference' => $data['reference'] ?? null,
            'proof_image_path' => 'uploads/payment-proofs/'.$filename,
            'metadata' => ['source' => 'customer_visit_payment_proof'],
        ]);

        return back()
            ->with('success', 'Payment proof uploaded. Please show the screenshot to staff for confirmation.')
            ->withCookie($visits->cookie($visit));
    }
}
