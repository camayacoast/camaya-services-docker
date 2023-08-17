<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class GuestVehicle extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'guest_vehicles';

    protected $fillable = [
        'booking_reference_number',
        'model',
        'plate_number',
    ];

    public function booking()
    {
        return $this->belongsTo('App\Models\Booking\Booking', 'booking_reference_number', 'reference_number');
    }
}
