<?php

namespace App\Models\Hotel;

use Illuminate\Database\Eloquent\Model;

class RoomTypeImage extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'room_type_images';
    
    protected $fillable = [
        'room_type_id',
        'image_path',
        'cover',
    ];
}
