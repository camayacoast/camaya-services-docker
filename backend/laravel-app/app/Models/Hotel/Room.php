<?php

namespace App\Models\Hotel;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'rooms';
    
    protected $fillable = [
        'id',
        'property_id',
        'room_type_id',
        'number',
        'room_status',
        'fo_status',
        'reservation_status',
        'description',
        'enabled',
    ];

    public function type()
    {
        return $this->hasOne('App\Models\Hotel\RoomType', 'id', 'room_type_id');
    }

    public function property()
    {
        return $this->hasOne('App\Models\Hotel\Property', 'id', 'property_id');
    }


    public function room_status()
    {
        return $this->hasMany('App\Models\Hotel\RoomStatus', 'id', 'room_id');
    }

    public function room_reservations()
    {
        return $this->hasMany('App\Models\Hotel\RoomReservation', 'room_id', 'id');
    }
}
