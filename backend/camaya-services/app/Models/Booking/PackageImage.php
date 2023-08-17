<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class PackageImage extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'package_images';

    protected $fillable = [
        'package_id',
        'image_path',
        'cover',
    ];
}
