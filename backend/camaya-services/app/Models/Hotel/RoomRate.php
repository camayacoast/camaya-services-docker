<?php

namespace App\Models\Hotel;

use Illuminate\Database\Eloquent\Model;

class RoomRate extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'room_rates';
    
    protected $fillable = [
        'room_type_id',
        'start_datetime',
        'end_datetime',
        'room_rate',
        'days_interval',
        'exclude_days',
        'description',
        'created_by',
        'status',
        'allowed_roles',
    ];

    protected $casts = [
        'exclude_days' => 'array', // Will convert to (Array)
        'days_interval' => 'array', // Will convert to (Array)
        'allowed_roles' => 'array', // Will convert to (Array)
    ];

    public function room_type()
    {
        return $this->hasOne('App\Models\Hotel\RoomType', 'id', 'room_type_id');
    }
}
