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
    public const MINUTES = 90;
    private const OPEN_SESSION_YEARS = 10;
    private const TOUCH_AFTER_MINUTES = 10;

    public function resolve(Request $request, Restaurant $restaurant, RestaurantTable $table): GuestSession
    {
        $visit = $this->current($request, $restaurant, $table);

        if (! $visit) {
            $visit = GuestSession::create([
                'restaurant_id' => $restaurant->id,
                'table_id' => $table->id,
                'table_number' => $table->table_number,
                'token' => Str::random(64),
                'expires_at' => now()->addMinutes(self::MINUTES),
                'last_seen_at' => now(),
            ]);
        } elseif ($this->shouldTouch($visit)) {
            $visit->update([
                'expires_at' => now()->addMinutes(self::MINUTES),
                'last_seen_at' => now(),
            ]);
        }

        return $visit;
    }

    public function current(Request $request, Restaurant $restaurant, RestaurantTable $table): ?GuestSession
    {
        $token = $request->cookie($this->cookieName($restaurant, $table));

        if (! $token) {
            return null;
        }

        $visit = GuestSession::where('restaurant_id', $restaurant->id)
            ->where('table_id', $table->id)
            ->where('token', $token)
            ->whereNull('closed_at')
            ->first();

        if (! $visit) {
            return null;
        }

        if ($visit->expires_at?->isFuture() || $visit->hasUnsettledWork()) {
            if ($visit->expires_at?->isPast()) {
                $this->keepOpen($visit);
            }

            return $visit;
        }

        return null;
    }

    public function touch(GuestSession $visit): void
    {
        if ($visit->hasUnsettledWork()) {
            $this->keepOpen($visit);
            return;
        }

        if ($this->shouldTouch($visit)) {
            $visit->update([
                'expires_at' => now()->addMinutes(self::MINUTES),
                'last_seen_at' => now(),
            ]);
        }
    }

    public function keepOpen(GuestSession $visit): void
    {
        $visit->update([
            'expires_at' => now()->addYears(self::OPEN_SESSION_YEARS),
            'last_seen_at' => now(),
        ]);
    }

    public function closeIfSettled(?int $guestSessionId): void
    {
        if (! $guestSessionId) {
            return;
        }

        $visit = GuestSession::find($guestSessionId);
        if (! $visit || $visit->closed_at || $visit->hasUnsettledWork()) {
            return;
        }

        if ($visit->orders()->exists()) {
            $visit->update([
                'closed_at' => now(),
                'expires_at' => now(),
                'last_seen_at' => now(),
            ]);
        }
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

    private function shouldTouch(GuestSession $visit): bool
    {
        return $visit->last_seen_at === null
            || $visit->last_seen_at->lte(now()->subMinutes(self::TOUCH_AFTER_MINUTES))
            || $visit->expires_at === null
            || $visit->expires_at->lte(now()->addMinutes(self::TOUCH_AFTER_MINUTES));
    }
}
