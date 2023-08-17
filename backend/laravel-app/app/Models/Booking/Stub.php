<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class Stub extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'stubs';

    protected $fillable = [
        'type',
        'interfaces',
        'mode',
        'count',
        'category',
        'starttime',
        'endtime',
    ];


    protected $casts = [
        'interfaces' => 'array',
    ];

}
