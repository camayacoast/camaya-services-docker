<?php

namespace App\Models\Golf;

use Illuminate\Database\Eloquent\Model;

class TeeTimeSchedule extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'tee_time_schedules';

    protected $fillable = [
        'date',
        'time',
        'entity',
        'allocation',
        'mode_of_transportation',
        'status',
        'created_by',
    ];

    public function guests()
    {
        return $this->hasMany('App\Models\Golf\TeeTimeGuestSchedule', 'tee_time_schedule_id', 'id')->whereNull('deleted_at');
    }
}
