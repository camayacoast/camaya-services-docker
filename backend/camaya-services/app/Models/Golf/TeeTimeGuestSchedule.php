<?php

namespace App\Models\Golf;

use Illuminate\Database\Eloquent\Model;

class TeeTimeGuestSchedule extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'tee_time_guest_schedules';

    protected $fillable = [
        'booking_reference_number',
        'guest_reference_number',
        'tee_time_schedule_id',
        'status',
        'deleted_by',
        'deleted_at',
    ];

    public function guest()
    {
        return $this->hasOne('App\Models\Booking\Guest', 'guest_reference_number', 'reference_number')->whereNull('deleted_at');
    }

    public function schedule()
    {
        return $this->hasOne('App\Models\Golf\TeeTimeSchedule', 'id', 'tee_time_schedule_id');
    }
}
