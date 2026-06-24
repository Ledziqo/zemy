<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATUSES = ['new', 'preparing', 'served', 'paid', 'completed', 'cancelled'];
    public const ORDER_TYPES = ['dine_in', 'delivery'];
    public const PAYMENT_METHODS = ['cash', 'telebirr', 'cbe', 'awash', 'abyssinia'];

    protected $fillable = [
        'restaurant_id', 'table_id', 'guest_session_id', 'table_number', 'customer_name', 'customer_phone', 'note',
        'status', 'payment_method', 'payment_status', 'subtotal', 'service_charge', 'tax', 'total',
        'handled_by_profile_id', 'order_type',
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
    public function guestSession() { return $this->belongsTo(GuestSession::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function payment() { return $this->hasOne(Payment::class); }
    public function handledByProfile() { return $this->belongsTo(StaffProfile::class, 'handled_by_profile_id'); }
}