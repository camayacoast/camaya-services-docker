<?php

namespace App\Models\Transportation;

use Illuminate\Database\Eloquent\Model;

class SeatAllocation extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'seat_allocations';
    
    protected $fillable = [
        'schedule_id',
        'name',
        'category',
        'quantity',
        'allowed_roles',
    ];

    protected $casts = [
        'allowed_roles' => 'array'
    ];

    public function segments()
    {
        return $this->hasMany('App\Models\Transportation\SeatSegment', 'seat_allocation_id', 'id');
    }
}
