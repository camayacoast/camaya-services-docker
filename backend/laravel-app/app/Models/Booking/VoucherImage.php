<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class VoucherImage extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'voucher_images';

    protected $fillable = [
        'voucher_id',
        'image_path',
        'cover',
    ];
}
