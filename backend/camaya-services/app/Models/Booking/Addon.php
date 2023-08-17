<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    //

    protected $connection = 'camaya_booking_db';
    protected $table = 'addons';

    protected $fillable = [
        'booking_reference_number',
        'guest_reference_number',

        'code',
        'type',
        'date',

        'status',

        'created_by',
        'created_at',
    ];

    public function booking()
    {
        return $this->belongsTo('App\Models\Booking\Booking', 'booking_reference_number', 'reference_number');
    }
}
