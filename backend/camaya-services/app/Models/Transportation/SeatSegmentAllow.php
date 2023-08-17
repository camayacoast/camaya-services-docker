<?php

namespace App\Models\Transportation;

use Illuminate\Database\Eloquent\Model;

class SeatSegmentAllow extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'seat_segment_allows';
    
    protected $fillable = [
        'seat_segment_id',
        'role_id', // nullable
        'user_id', // nullable
    ];
}
