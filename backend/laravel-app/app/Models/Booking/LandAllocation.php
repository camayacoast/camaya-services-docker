<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class LandAllocation extends Model
{
    protected $connection = 'camaya_booking_db';
    protected $table = 'land_allocations';

    protected $fillable = [
        'date',
        'allocation',
        'used',
        'entity',
        'owner_id',
        'allowed_roles',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'allowed_roles' => 'array', // Will converted to (Array)
    ];

    public function allowed_users()
    {
        return $this->hasMany('App\Models\Booking\LandAllocationUser', 'land_allocation_id', 'id');
    }

    public function owner()
    {
        return $this->hasOne('App\User', 'id', 'owner_id');
    }
}
