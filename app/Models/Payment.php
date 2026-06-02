<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['restaurant_id', 'order_id', 'amount', 'method', 'status', 'reference', 'metadata'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'metadata' => 'array'];
    }

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
    public function order() { return $this->belongsTo(Order::class); }
}
