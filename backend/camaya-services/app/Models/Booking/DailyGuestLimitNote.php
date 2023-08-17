<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class DailyGuestLimitNote extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'daily_guest_limit_notes';

    protected $fillable = [
    
        'date',
        'note',
        'updated_by',

        'created_at',
        'updated_at',

    ];
}
