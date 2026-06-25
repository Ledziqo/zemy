<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    protected $fillable = ['name', 'restaurant_name', 'business_type', 'phone', 'email', 'location', 'message', 'status'];
}
