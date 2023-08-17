<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class ProductAllowSource extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'product_allow_sources';

    protected $fillable = [
        'product_id',
        'source',
    ];
}
