<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $fillable = ['restaurant_id', 'table_number', 'table_name', 'qr_code_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
    public function orders() { return $this->hasMany(Order::class, 'table_id'); }
}
