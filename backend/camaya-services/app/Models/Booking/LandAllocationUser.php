<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class LandAllocationUser extends Model
{
    protected $connection = 'camaya_booking_db';
    protected $table = 'land_allocation_users';

    protected $fillable = [
        'user_id',
        'land_allocation_id',
    ];

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }
}
