<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    public const DASHBOARD_ACCESS_STATUSES = ['active', 'payment_required', 'revoked'];
    public const BUSINESS_TYPES = ['restaurant', 'hotel'];

    protected $fillable = [
        'name', 'slug', 'business_type', 'phone', 'email', 'location', 'logo_path', 'cover_image_path',
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
    public function guestSessions() { return $this->hasMany(GuestSession::class); }

    public function isHotel(): bool
    {
        return $this->business_type === 'hotel';
    }

    public function businessTypeLabel(): string
    {
        return $this->isHotel() ? 'Hotel' : 'Restaurant';
    }

    public function locationLabel(bool $plural = false): string
    {
        if ($this->isHotel()) {
            return $plural ? 'rooms' : 'room';
        }

        return $plural ? 'tables' : 'table';
    }

    public function locationLabelTitle(bool $plural = false): string
    {
        return ucfirst($this->locationLabel($plural));
    }

    public function staffRequestLabel(): string
    {
        return $this->isHotel() ? 'Call Staff' : 'Call Waiter';
    }

    public function requestTypeLabel(string $type): string
    {
        return match ($type) {
            'call_waiter' => $this->staffRequestLabel(),
            'request_bill' => $this->isHotel() ? 'Request Room Bill' : 'Request Bill',
            'request_water' => 'Request Water',
            default => 'Other',
        };
    }
}
