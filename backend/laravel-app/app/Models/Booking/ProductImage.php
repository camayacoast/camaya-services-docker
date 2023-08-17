<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'image_path',
        'cover',
    ];
}
