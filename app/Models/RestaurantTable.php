<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $fillable = ['restaurant_id', 'table_number', 'location_type', 'table_name', 'qr_code_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
    public function orders() { return $this->hasMany(Order::class, 'table_id'); }

    public function isDiningTable(): bool
    {
        if ($this->location_type !== null) {
            return $this->location_type === 'table';
        }

        return str_starts_with($this->table_number, 'restaurant-')
            || str_starts_with($this->table_number, 'lobby-');
    }

    public function isRoomServicePoint(): bool
    {
        if ($this->location_type !== null) {
            return $this->location_type === 'room';
        }

        if ($this->isDiningTable()) {
            return false;
        }

        if ($this->restaurant?->isHotel()) {
            return true;
        }

        return $this->restaurant?->isBoth() === true
            && preg_match('/^(room|suite)\b/i', trim((string) $this->table_name)) === 1;
    }

    public function scanInstruction(): string
    {
        return $this->isRoomServicePoint() ? 'Scan for room service' : 'Scan to order';
    }

    public function locationTypeLabel(): string
    {
        return $this->isRoomServicePoint() ? 'Room' : 'Table';
    }

    public function displayLabel(): string
    {
        if ($this->table_name) {
            return $this->table_name;
        }

        if ($this->isRoomServicePoint()) {
            return 'Room '.$this->table_number;
        }

        if (str_starts_with($this->table_number, 'restaurant-')) {
            return 'Restaurant Table '.substr($this->table_number, strlen('restaurant-'));
        }

        if (str_starts_with($this->table_number, 'lobby-')) {
            return 'Lobby Table '.substr($this->table_number, strlen('lobby-'));
        }

        return ($this->isRoomServicePoint() ? 'Room ' : 'Table ').$this->table_number;
    }
}
