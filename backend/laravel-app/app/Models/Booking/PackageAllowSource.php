<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class PackageAllowSource extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'package_allow_sources';

    protected $fillable = [
        'package_id',
        'source',
    ];
}
