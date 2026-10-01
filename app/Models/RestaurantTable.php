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
        if ($this->isRoomServicePoint()) {
            if ($this->table_name) {
                return preg_replace('/^Hotel\s+Room\b/i', 'Room', $this->table_name);
            }

            return 'Room '.$this->table_number;
        }

        if (str_starts_with($this->table_number, 'restaurant-')) {
            return '(R) Table '.substr($this->table_number, strlen('restaurant-'));
        }

        if (str_starts_with($this->table_number, 'lobby-')) {
            return '(L) Table '.substr($this->table_number, strlen('lobby-'));
        }

        if ($this->table_name && preg_match('/^Restaurant\s+Table\b\s*(.*)$/i', $this->table_name, $matches) === 1) {
            return '(R) Table '.trim($matches[1]);
        }

        if ($this->table_name && preg_match('/^Lobby\s+Table\b\s*(.*)$/i', $this->table_name, $matches) === 1) {
            return '(L) Table '.trim($matches[1]);
        }

        if ($this->table_name) {
            return $this->table_name;
        }

        return 'Table '.$this->table_number;
    }
}
