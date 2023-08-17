<?php

namespace App\Models\Transportation;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'locations';
    
    protected $fillable = [
        'name',
        'code',
        'description',
        'location',
        'latitude',
        'longitude',
        'icon',
    ];
}
