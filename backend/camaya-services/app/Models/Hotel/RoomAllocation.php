<?php

namespace App\Models\Hotel;

use Illuminate\Database\Eloquent\Model;

class RoomAllocation extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'room_allocations';
    
    protected $fillable = [
        'room_type_id',
        'date',
        'allocation',
        'entity',
        'allowed_roles',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'allowed_roles' => 'array', // Will converted to (Array)
    ];

    /**
     * Relationships
     */

    public function room_type()
    {
        return $this->hasOne('App\Models\Hotel\RoomType', 'id', 'room_type_id');
    }

    public function room_reservation()
    {
        return $this->hasOne('App\Models\Hotel\RoomReservation', 'room_allocation_id', 'id');
    }
}
