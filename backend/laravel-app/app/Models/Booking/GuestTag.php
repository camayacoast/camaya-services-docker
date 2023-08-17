<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class GuestTag extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'guest_tags';

    protected $fillable = [
        'guest_reference_number',
        'name',
        'created_by',
    ];
}
