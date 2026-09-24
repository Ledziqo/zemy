<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\WorkBoardRevision;

class ServiceRequest extends Model
{
    protected $fillable = ['restaurant_id', 'table_id', 'guest_session_id', 'table_number', 'type', 'note', 'status'];

    protected static function booted(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::{$event}(fn (self $request) => WorkBoardRevision::bumpAfterCommit((int) $request->restaurant_id));
        }
    }

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
    public function table() { return $this->belongsTo(RestaurantTable::class, 'table_id'); }
    public function guestSession() { return $this->belongsTo(GuestSession::class); }
}
