<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class ReservationPromo extends Model
{
    //

    protected $fillable = [
      'reservation_number',
      'promo_type',  
    ];
    
}
