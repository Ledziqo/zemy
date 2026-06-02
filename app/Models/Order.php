<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATUSES = ['new', 'preparing', 'served', 'paid', 'completed', 'cancelled'];

    protected $fillable = [
        'restaurant_id', 'table_id', 'table_number', 'customer_name', 'customer_phone', 'note',
        'status', 'payment_method', 'payment_status', 'subtotal', 'service_charge', 'tax', 'total',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'service_charge' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
    public function table() { return $this->belongsTo(RestaurantTable::class, 'table_id'); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function payment() { return $this->hasOne(Payment::class); }
}
