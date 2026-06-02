<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['restaurant_id', 'plan_name', 'monthly_price', 'status', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['monthly_price' => 'decimal:2', 'starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
}
