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

    public function displayLabel(): string
    {
        if ($this->table_name) {
            return $this->table_name;
        }

        if (str_starts_with($this->table_number, 'restaurant-')) {
            return 'Restaurant Table '.substr($this->table_number, strlen('restaurant-'));
        }

        if (str_starts_with($this->table_number, 'lobby-')) {
            return 'Lobby Table '.substr($this->table_number, strlen('lobby-'));
        }

        return ($this->restaurant?->isHotel() ? 'Room ' : 'Table ').$this->table_number;
    }
}
