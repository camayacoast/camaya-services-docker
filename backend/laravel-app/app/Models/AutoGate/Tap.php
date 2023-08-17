<?php

namespace App\Models\AutoGate;

use Illuminate\Database\Eloquent\Model;

class Tap extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'taps';

    protected $fillable = [

        // time status and message
        'code',
        'tap_datetime',
        'status',
        'message',

        // location, kiosk used and type
        'location',
        'kiosk_id',
        'type',

        // nullable
        'pass_code',
        
        
        // Secondary
        'lat',
        'long',
        
    ];

    public function guest()
    {
        return $this->belongsTo('App\Models\Booking\Guest', 'code', 'reference_number');
    }
}
