<?php

namespace App\Models\Hotel;

use Illuminate\Database\Eloquent\Model;

use App\Models\Hotel\Room;
use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomTypeImage;
use Illuminate\Support\Facades\DB;


class RoomType extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'room_types';
    
    protected $fillable = [
        'property_id',
        'name',
        'code',
        'description',
        'capacity',
        'max_capacity',
        'rack_rate',
        'cover_image_path',
        'status',
    ];


    public function rooms()
    {
        return $this->hasMany('App\Models\Hotel\Room', 'room_type_id', 'id');
    }

    public function enabledRooms()
    {
        return $this->hasMany('App\Models\Hotel\Room', 'room_type_id', 'id')->where('rooms.enabled', 1);
    }

    public function property()
    {
        return $this->belongsTo('App\Models\Hotel\Property', 'property_id', 'id');
    }


    public function images()
    {
        return $this->hasMany('App\Models\Hotel\RoomTypeImage', 'room_type_id', 'id')->orderBy('cover', 'asc');
    }

    public function allocations()
    {
        return $this->hasMany('App\Models\Hotel\RoomAllocation', 'room_type_id', 'id');
    }

}
