<?php

namespace App\Models\Transportation;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'routes';
    
    protected $fillable = [
        'origin_id',
        'destination_id',
    ];

    public function origin()
    {
        return $this->hasOne('App\Models\Transportation\Location', 'id', 'origin_id');
    }

    public function destination()
    {
        return $this->hasOne('App\Models\Transportation\Location', 'id', 'destination_id');
    }
}
