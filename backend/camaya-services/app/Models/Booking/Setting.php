<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'settings';

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'value',
        'updated_by',
    ];
}
