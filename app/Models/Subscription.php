<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['restaurant_id', 'plan_name', 'monthly_price', 'status', 'starts_at', 'ends_at', 'payment_method'];

    protected function casts(): array
    {
        return ['monthly_price' => 'decimal:2', 'starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function daysRemaining(): ?int
    {
        if (! $this->ends_at) return null;

        return (int) today()->diffInDays($this->ends_at, false);
    }

    public function isExpired(): bool
    {
        return ($this->daysRemaining() ?? 0) < 0;
    }

    public const PAYMENT_GRACE_DAYS = 7;

    public function graceEndsAt(): ?\Carbon\CarbonInterface
    {
        return $this->ends_at?->copy()->addDays(self::PAYMENT_GRACE_DAYS)->endOfDay();
    }

    public function isInPaymentGrace(): bool
    {
        return $this->isExpired() && $this->allowsServiceAccess();
    }

    public function allowsServiceAccess(): bool
    {
        if (!in_array($this->status, ['active', 'trial', 'unpaid'], true)) return false;
        if (!$this->ends_at) return $this->status !== 'unpaid';
        return now()->lte($this->graceEndsAt());
    }

    public function effectiveStatus(): string
    {
        return $this->isExpired() ? 'expired' : $this->status;
    }

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
}
