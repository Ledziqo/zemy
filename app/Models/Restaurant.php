<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    public const DASHBOARD_ACCESS_STATUSES = ['active', 'payment_required', 'revoked'];

    protected $fillable = [
        'name', 'slug', 'phone', 'email', 'location', 'logo_path', 'cover_image_path',
        'primary_color', 'is_active', 'dashboard_access_status', 'settings',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'settings' => 'array'];
    }

    public function users() { return $this->hasMany(User::class); }
    public function categories() { return $this->hasMany(Category::class)->orderBy('sort_order')->orderBy('name'); }
    public function menuItems() { return $this->hasMany(MenuItem::class); }
    public function tables() { return $this->hasMany(RestaurantTable::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function serviceRequests() { return $this->hasMany(ServiceRequest::class); }
    public function subscriptions() { return $this->hasMany(Subscription::class); }
}
