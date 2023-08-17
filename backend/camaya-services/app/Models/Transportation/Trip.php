<?php

namespace App\Models\Transportation;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'trips';
    
    protected $fillable = [
        'trip_number',
        'ticket_reference_number',
        'guest_reference_number',
        'booking_reference_number',
        'passenger_id',
        'seat_number',
        'status',
        'seat_segment_id',
        'printed',
        'last_printed_at',
        'checked_in_at',
        'boarded_at',
        'cancelled_at',
        'no_show_at',
    ];


    public function passenger() {
        return $this->hasOne('App\Models\Transportation\Passenger', 'id', 'passenger_id');
    }

    public function booking() {
        return $this->hasOne('App\Models\Booking\Booking', 'reference_number', 'booking_reference_number');
    }

    public function schedule() {
        return $this->belongsTo('App\Models\Transportation\Schedule', 'trip_number', 'trip_number');
    }

    public function seatSegments()
    {
        return $this->hasMany('App\Models\Transportation\SeatSegment', 'trip_number', 'trip_number');
    }

    //
}
