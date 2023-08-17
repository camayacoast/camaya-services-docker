<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'notes';

    protected $fillable = [
        'booking_reference_number',
        'author',
        'message',
    ];

    public function author_details()
    {
        return $this->hasOne('App\User', 'id', 'author');
    }
}
