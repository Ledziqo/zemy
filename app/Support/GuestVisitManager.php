<?php

namespace App\Support;

use App\Models\GuestSession;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class GuestVisitManager
{
    public const MINUTES = 120;

    public function resolve(Request $request, Restaurant $restaurant, RestaurantTable $table): GuestSession
    {
        $cookieName = $this->cookieName($restaurant, $table);
        $token = $request->cookie($cookieName);

        $visit = $token
            ? GuestSession::where('restaurant_id', $restaurant->id)
                ->where('table_id', $table->id)
                ->where('token', $token)
                ->whereNull('closed_at')
                ->where('expires_at', '>', now())
                ->first()
            : null;

        if (! $visit) {
            $visit = GuestSession::create([
                'restaurant_id' => $restaurant->id,
                'table_id' => $table->id,
                'table_number' => $table->table_number,
                'token' => Str::random(64),
                'expires_at' => now()->addMinutes(self::MINUTES),
                'last_seen_at' => now(),
            ]);
        } else {
            $visit->update([
                'expires_at' => now()->addMinutes(self::MINUTES),
                'last_seen_at' => now(),
            ]);
        }

        return $visit;
    }

    public function cookieName(Restaurant $restaurant, RestaurantTable $table): string
    {
        return 'zemtab_visit_'.$restaurant->id.'_'.$table->id;
    }

    public function cookie(GuestSession $visit): Cookie
    {
        return cookie(
            $this->cookieName($visit->restaurant, $visit->table),
            $visit->token,
            self::MINUTES,
            null,
            null,
            null,
            true,
            false,
            'lax'
        );
    }
}
