<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
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
            $daysLeft = $sub?->daysRemaining();

            if (! $sub) {
                return (object) [
                    'restaurant' => $restaurant, 'subscription' => null, 'daysLeft' => null,
                    'statusLabel' => 'No subscription', 'statusColor' => 'border-zem-border text-zem-muted',
                ];
            }

            $isExpired = $sub->isExpired();
            $isExpiring = $daysLeft !== null && $daysLeft >= -(Subscription::PAYMENT_GRACE_DAYS - 1) && $daysLeft <= Subscription::PAYMENT_GRACE_DAYS;

            if ($isExpired) { $summary['expired']++; }
            elseif ($sub->status === 'unpaid') { $summary['unpaid']++; }
            elseif ($isExpiring) { $summary['expiring']++; }
            else { $summary['active']++; }

            $color = $isExpired ? 'bg-red-100 text-red-700 border-red-300'
                : ($isExpiring ? 'bg-zem-gold/20 text-zem-gold border-zem-gold/40'
                : ($sub->status === 'unpaid' ? 'bg-red-100 text-red-700 border-red-300'
                : 'bg-green-100 text-green-700 border-green-300'));

            $label = $sub->isInPaymentGrace() ? 'Payment due — grace until '.$sub->graceEndsAt()->format('M j') : ($isExpired ? 'Suspended' : ($isExpiring ? $daysLeft . ' days left' : ($sub->status === 'unpaid' ? 'Unpaid' : 'Active')));

            return (object) [
                'restaurant' => $restaurant, 'subscription' => $sub, 'daysLeft' => $daysLeft,
                'statusLabel' => $label, 'statusColor' => $color,
            ];
        });

        return view('admin.payments.index', ['rows' => $rows, 'summary' => $summary]);
    }

    public function settings()
    {
        $settings = [
            'telebirr_number' => config('payment.telebirr'),
            'cbe_account' => config('payment.cbe'),
            'awash_account' => config('payment.awash'),
            'abyssinia_account' => config('payment.abyssinia'),
            'telegram' => config('payment.telegram'),
        ];

        return view('admin.payments.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'telebirr_number' => ['nullable', 'string', 'max:255'],
            'cbe_account' => ['nullable', 'string', 'max:255'],
            'awash_account' => ['nullable', 'string', 'max:255'],
            'abyssinia_account' => ['nullable', 'string', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
        ]);

        $this->updateEnv([
            'PAYMENT_TELEBIRR' => $data['telebirr_number'] ?? '',
            'PAYMENT_CBE' => $data['cbe_account'] ?? '',
            'PAYMENT_AWASH' => $data['awash_account'] ?? '',
            'PAYMENT_ABYSSINIA' => $data['abyssinia_account'] ?? '',
            'PAYMENT_TELEGRAM' => $data['telegram'] ?? '',
        ]);

        Artisan::call('config:clear');

        return back()->with('success', 'Payment settings saved.');
    }

    private function updateEnv(array $updates): void
    {
        $path = base_path('.env');
        if (! file_exists($path) || ! is_writable($path)) return;

        $contents = file_get_contents($path);
        foreach ($updates as $key => $value) {
            $line = $key.'='.$this->escapeEnvValue($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            if (preg_match($pattern, $contents)) {
                $contents = preg_replace_callback($pattern, fn () => $line, $contents);
            } else {
                $contents .= PHP_EOL . $line;
            }
        }
        File::replace($path, $contents);
    }

    private function escapeEnvValue(string $value): string
    {
        $escaped = str_replace(
            ['\\', '"', "\r", "\n"],
            ['\\\\', '\\"', '', '\\n'],
            $value
        );

        return '"'.$escaped.'"';
    }

    public function markPaid(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'payment_method' => ['nullable', 'string', 'max:255'],
            'extend_days' => ['nullable', 'integer', 'min:30', 'max:3600', 'multiple_of:30'],
            'renew_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'custom_ends_at' => ['nullable', 'date', 'after:today'],
        ]);

        if (!empty($data['custom_ends_at'])) {
            $nextDue = Subscription::monthlyDueDate($data['custom_ends_at']);
        } else {
            // Old cached forms can still submit +30/+60 days. Convert these to
            // calendar months; never let day arithmetic move billing off the 1st.
            $months = (int) ($data['renew_months'] ?? (isset($data['extend_days']) ? (int) ceil($data['extend_days'] / 30) : 1));
            $due = $subscription->paymentDueAt();
            $base = $due && $due->greaterThan(today()) ? $due : today()->startOfMonth();
            $nextDue = $base->copy()->addMonthsNoOverflow($months);
        }

        $subscription->update([
            'status' => 'active',
            'ends_at' => $nextDue->toDateString(),
            'payment_method' => $data['payment_method'] ?? $subscription->payment_method,
        ]);

        if (Schema::hasColumn('restaurants', 'dashboard_access_status')) {
            $subscription->restaurant->update(['dashboard_access_status' => 'active']);
        }

        return back()->with('success', 'Payment verified. Next payment due '.$nextDue->format('M j, Y').'. Access is restored immediately.');
    }
}
