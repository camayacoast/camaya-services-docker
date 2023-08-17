<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class DailyGuestLimit extends Model
{
    //

    protected $connection = 'camaya_booking_db';
    protected $table = 'daily_guest_limits';

    protected $fillable = [
        'date',
        'limit',
        'category',
        'status',
        'created_by',
        'approved_at',
        'approved_by',
        'created_at',
        'updated_at',
    ];
}
