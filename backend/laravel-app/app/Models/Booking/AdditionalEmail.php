<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class AdditionalEmail extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'additional_emails';

    protected $fillable = [
        'booking_id',
        'email',
        'created_by',
        'created_at',
        'deleted_at'
    ];
}
