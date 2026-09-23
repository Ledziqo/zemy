<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkBoardRevision
{
    public static function current(int $restaurantId): string
    {
        return now()->toDateString().':'.(string) Cache::store('file')->get(self::key($restaurantId), 'initial');
    }

    public static function bump(int $restaurantId): void
    {
        Cache::store('file')->forever(self::key($restaurantId), Str::random(40));
    }

    public static function bumpAfterCommit(int $restaurantId): void
    {
        $connection = DB::connection();
        if ($connection->transactionLevel() > 0) {
            $connection->afterCommit(fn () => self::bump($restaurantId));
            return;
        }

        self::bump($restaurantId);
    }

    private static function key(int $restaurantId): string
    {
        return "work_board_revision:{$restaurantId}";
    }
}
