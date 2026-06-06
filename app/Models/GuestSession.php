<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestSession extends Model
{
    protected $fillable = [
        'restaurant_id', 'table_id', 'table_number', 'token', 'expires_at', 'last_seen_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
    public function table() { return $this->belongsTo(RestaurantTable::class, 'table_id'); }
    public function orders() { return $this->hasMany(Order::class); }
    public function serviceRequests() { return $this->hasMany(ServiceRequest::class); }
    public function payments() { return $this->hasMany(Payment::class); }

    public function isOpen(): bool
    {
        return $this->closed_at === null && $this->expires_at?->isFuture();
    }
}
