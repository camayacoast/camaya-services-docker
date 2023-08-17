<?php

namespace App\Models\Hotel;

use Illuminate\Database\Eloquent\Model;

class RoomBlockingAllowRoles extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'room_blocking_allow_roles';
    
    protected $fillable = [
        'room_reservation_id',
        'role_id',
    ];
}
