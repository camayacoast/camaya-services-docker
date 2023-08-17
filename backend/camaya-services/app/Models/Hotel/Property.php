<?php

namespace App\Models\Hotel;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    //

    protected $connection = 'camaya_booking_db';
    protected $table = 'properties';
    
    protected $fillable = [
        'name',
        'code',
        'type',
        'address',
        'phone_number',
        'floors',
        'cover_image_path',
        'description',
        'status',
    ];

    public function amenities()
    {
        return $this->hasMany('App\Models\Hotel\Amenity', 'property_id', 'id');
    }

    public function room_types()
    {
        return $this->hasMany('App\Models\Hotel\RoomType', 'property_id', 'id');
    }

    public function rooms()
    {
        return $this->hasMany('App\Models\Hotel\Room', 'property_id', 'id');
    }
}
