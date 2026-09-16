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

    public function effectiveStatus(): string
    {
        return $this->isExpired() ? 'expired' : $this->status;
    }

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
}
