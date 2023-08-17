<?php

namespace App\Models\Hotel;

use Illuminate\Database\Eloquent\Model;

class RoomBlockingAllowSources extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'room_blocking_allow_sources';
    
    protected $fillable = [
        'room_reservation_id',
        'source',
    ];
}
