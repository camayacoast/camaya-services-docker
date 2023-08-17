<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'activity_logs';

    protected $fillable = [
        'booking_reference_number',
        
        'action',
        'description',

        'model',
        'model_id',

        'properties',

        'created_by',
        'created_at',
    ];


    /**
     *  Causer
     */
    public function causer()
    {
        return $this->belongsTo('App\User', 'created_by', 'id');
    }
}
