<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffProfile extends Model
{
    public const ROLES = ['owner_manager', 'cashier', 'kitchen'];

    protected $fillable = [
        'restaurant_id', 'name', 'role', 'password', 'is_active',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'handled_by_profile_id');
    }

    public function isOwnerManager(): bool
    {
        return $this->role === 'owner_manager';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function isKitchen(): bool
    {
        return $this->role === 'kitchen';
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'owner_manager' => 'Owner/Manager',
            'cashier' => 'Cashier',
            'kitchen' => 'Kitchen',
            default => ucfirst($this->role),
        };
    }
}