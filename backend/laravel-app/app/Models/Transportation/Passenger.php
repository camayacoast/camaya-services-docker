<?php

namespace App\Models\Transportation;

use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'passengers';

    protected $fillable = [
        'trip_number',
        'booking_reference_number',
        'guest_reference_number',
        'first_name',
        'last_name',
        'age',
        'nationality',
        'type',
        'address',
    ];

    public function trip()
    {
        return $this->hasOne('App\Models\Transportation\Trip', 'passenger_id', 'id');
    }

    public function booking()
    {
        return $this->hasOne('App\Models\Booking\Booking', 'reference_number', 'booking_reference_number');
    }

    public function guest()
    {
        return $this->hasOne('App\Models\Booking\Guest', 'reference_number', 'guest_reference_number');
    }

    public function guest_tags()
    {
        return $this->hasMany('App\Models\Booking\GuestTag', 'guest_reference_number', 'guest_reference_number');
    }

    public function ticket()
    {
        return $this->hasOne('App\Models\OneBITS\Ticket', 'passenger_id', 'id');
    }
}
