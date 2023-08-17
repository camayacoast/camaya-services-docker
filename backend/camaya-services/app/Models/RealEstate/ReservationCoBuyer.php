<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class ReservationCoBuyer extends Model
{
    //
    protected $fillable = [
        'client_id',
        'reservation_id',
    ];


    public function details()
    {
        return $this->hasOne('App\Models\RealEstate\Client', 'id', 'client_id');
    }
}
