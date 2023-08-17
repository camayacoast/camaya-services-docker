<?php

namespace App\Models\Transportation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    //
    // use SoftDeletes;

    protected $connection = 'camaya_booking_db';
    protected $table = 'schedules';
    
    protected $fillable = [
        'route_id',
        'transportation_id',
        'trip_number',
        'trip_date',
        'status', // active, delayed, cancelled
        'start_time',
        'end_time',
        'updated_by',
        'created_by',
        'deleted_by',
    ];

    public function seatAllocations()
    {
        return $this->hasMany('App\Models\Transportation\SeatAllocation', 'schedule_id', 'id');
    }

    public function seatSegments()
    {
        return $this->hasMany('App\Models\Transportation\SeatSegment', 'trip_number', 'trip_number');
    }

    public function trips()
    {
        return $this->hasMany('App\Models\Transportation\Trip', 'trip_number', 'trip_number');
    }
    
    public function transportation()
    {
        return $this->hasOne('App\Models\Transportation\Transportation', 'id', 'transportation_id');
    }

    public function route()
    {
        return $this->hasOne('App\Models\Transportation\Route', 'id', 'route_id');
    }

    public static function generateTripNumber()
    {
        /**
         * Generate New Unique Booking Reference Number
         */ 
        $trip_number = "CT-".\Str::upper(\Str::random(6));

        // Creates a new reference number if it encounters duplicate
        while (self::where('trip_number', $trip_number)->exists()) {
            $trip_number = "CT-".\Str::upper(\Str::random(6));
        }

        return $trip_number;
    }
}
