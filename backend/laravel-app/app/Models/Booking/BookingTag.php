<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class BookingTag extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'booking_tags';

    protected $fillable = [
        'booking_id',
        'name',
        'created_by',
        'created_at',
        'deleted_at'
    ];
}
