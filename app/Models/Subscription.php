<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public const PAYMENT_GRACE_DAYS = 7;

    protected $fillable = ['restaurant_id', 'plan_name', 'monthly_price', 'status', 'starts_at', 'ends_at', 'payment_method'];

    protected static function booted(): void
    {
        static::saving(function (self $subscription) {
            $subscription->starts_at ??= today();
            $subscription->ends_at = $subscription->ends_at
                ? self::monthlyDueDate($subscription->ends_at)
                : $subscription->starts_at->copy()->startOfMonth()->addMonth();
        });
    }

    /** Move legacy dates forward to the first, never shorten an existing period. */
    public static function monthlyDueDate($date): \Carbon\CarbonInterface
    {
        $date = \Illuminate\Support\Carbon::parse($date)->startOfDay();
        return $date->day === 1 ? $date : $date->startOfMonth()->addMonth();
    }

    public function paymentDueAt(): ?\Carbon\CarbonInterface
    {
        return $this->ends_at ? self::monthlyDueDate($this->ends_at) : null;
    }

    protected function casts(): array
    {
        return ['monthly_price' => 'decimal:2', 'starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function daysRemaining(): ?int
    {
        $due = $this->paymentDueAt();
        return $due ? (int) today()->diffInDays($due, false) : null;
    }

    public function isExpired(): bool
    {
        return $this->graceEndsAt() && now()->greaterThan($this->graceEndsAt());
    }

    public function graceEndsAt(): ?\Carbon\CarbonInterface
    {
        // Due on the 1st: seven inclusive grace days (1st–7th), locked on the 8th.
        return $this->paymentDueAt()?->addDays(self::PAYMENT_GRACE_DAYS - 1)->endOfDay();
    }

    public function isInPaymentGrace(): bool
    {
        return $this->paymentDueAt() && now()->gte($this->paymentDueAt()) && $this->allowsServiceAccess();
    }

    public function graceDaysRemaining(): ?int
    {
        return $this->isInPaymentGrace()
            ? (int) today()->diffInDays($this->graceEndsAt()->startOfDay(), false) + 1
            : null;
    }

    public function allowsServiceAccess(): bool
    {
        if (!in_array($this->status, ['active', 'trial', 'unpaid'], true)) return false;
        if (!$this->ends_at) return $this->status !== 'unpaid';
        return now()->lte($this->graceEndsAt());
    }

    public function effectiveStatus(): string
    {
        if ($this->status === 'cancelled') return 'cancelled';
        if ($this->isExpired()) return 'expired';
        return $this->isInPaymentGrace() ? 'payment_due' : $this->status;
    }

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
}
