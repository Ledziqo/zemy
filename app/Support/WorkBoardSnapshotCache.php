<?php

namespace App\Support;

class WorkBoardSnapshotCache
{
    public static function key(int $restaurantId, string $revision, string $role, string $filter, int $page): string
    {
        return 'work_board_snapshot:'.$restaurantId.':'.sha1($revision.'|'.$role.'|'.$filter.'|'.$page);
    }
}
