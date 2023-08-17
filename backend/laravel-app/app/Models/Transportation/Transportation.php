<?php

namespace App\Models\Transportation;

use Illuminate\Database\Eloquent\Model;

class Transportation extends Model
{
    protected $connection = 'camaya_booking_db';
    protected $table = 'transportations';
    
    protected $fillable = [
        'name',
        'code',
        'type',
        'mode',
        'description',
        'capacity',
        'max_infant',
        'status',
        'current_location',
    ];

    public function seats()
    {
        return $this->hasMany('App\Models\Transportation\Seat', 'transportation_id', 'id');
    }

    public function activeSeats()
    {
        return $this->hasMany('App\Models\Transportation\Seat', 'transportation_id', 'id')->where('status', 'active');
    }

    public function outOfOrderSeats()
    {
        return $this->hasMany('App\Models\Transportation\Seat', 'transportation_id', 'id')->where('status', 'out-of-order');
    }

}
