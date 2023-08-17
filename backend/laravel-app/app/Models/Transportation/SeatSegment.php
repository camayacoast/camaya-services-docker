<?php

namespace App\Models\Transportation;

use Illuminate\Database\Eloquent\Model;

class SeatSegment extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'seat_segments';
    
    protected $fillable = [
        'trip_number',
        'seat_allocation_id',
        'name',
        'allocated',
        'rate',
        'active', // on-going
        'used', // on-going
        'booking_type', // all, DT, ON
        'status', // published, unpublished,
        'trip_link',
        'updated_by', 
    ];

    protected $casts = [
        'booking_type' => 'array'
    ];

    public function schedule()
    {
        return $this->belongsTo('App\Models\Transportation\Schedule', 'trip_number', 'trip_number');
    }

    public function seat_allocation()
    {
        return $this->belongsTo('App\Models\Transportation\SeatAllocation', 'seat_allocation_id', 'id');
    }


    public function allowed_roles()
    {
        return $this->hasMany('App\Models\Transportation\SeatSegmentAllow', 'seat_segment_id', 'id')->whereNotNull('role_id');
    }

    public function allowed_users()
    {
        return $this->hasMany('App\Models\Transportation\SeatSegmentAllow', 'seat_segment_id', 'id')->whereNotNull('user_id');
    }

    public function trips()
    {
        return $this->hasMany('App\Models\Transportation\Trip', 'seat_segment_id', 'id');
    }
}
