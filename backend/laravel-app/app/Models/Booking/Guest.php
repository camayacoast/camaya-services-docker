<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'guests';

    protected $fillable = [
        'booking_reference_number',
        'booking_id',
        'related_id',
        'reference_number',
        'first_name',
        'last_name',
        'age',
        'nationality',
        'type',
        'deleted_at',
        'deleted_by',
    ];

    public function booking()
    {
        return $this->belongsTo('App\Models\Booking\Booking', 'booking_reference_number', 'reference_number');
    }

    public function guestInclusions()
    {
        return $this->hasMany('App\Models\Booking\Inclusion', 'guest_id', 'id')->select('guest_id','code');
    }

    public function passes()
    {
        return $this->hasMany('App\Models\AutoGate\Pass', 'guest_reference_number', 'reference_number')->whereNull('deleted_at');
    }

    public function guestTags()
    {
        return $this->hasMany('App\Models\Booking\GuestTag', 'guest_reference_number', 'reference_number');
    }

    public function tripBookings()
    {
        return $this->hasMany('App\Models\Transportation\Trip', 'guest_reference_number', 'reference_number');
    }

    public function active_trips()
    {
        return $this->hasMany('App\Models\Transportation\Trip', 'guest_reference_number', 'reference_number')->whereNotIn('trips.status', ['cancelled', 'no_show']);
    }

    public function tee_time()
    {
        return $this->hasMany('App\Models\Golf\TeeTimeGuestSchedule', 'guest_reference_number', 'reference_number');
    }

    public function commercialEntry()
    {
        return $this->hasOne('App\Models\AutoGate\Tap', 'code', 'reference_number')
                    ->where('status', 'valid')
                    ->where('location', 'commercial_gate')
                    ->where('type', 'entry');
    }
}
