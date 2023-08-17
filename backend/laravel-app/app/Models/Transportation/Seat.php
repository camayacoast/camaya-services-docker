<?php

namespace App\Models\Transportation;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'seats';
    
    protected $fillable = [
        'transportation_id',
        'number',
        'type',
        'status',
        'auto_check_in_status',
        'order',
    ];


    public static function getLastActive($transportation_id)
    {
        return self::where('transportation_id')
                    ->where('status', 'active')
                    ->first();
    }
}
