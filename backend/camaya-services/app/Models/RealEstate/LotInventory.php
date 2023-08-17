<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class LotInventory extends Model
{
    //

    protected $fillable = [
        'subdivision',
        'subdivision_name',
        'phase',
        'block',
        'lot',
        'area',
        'type',
        'price_per_sqm',
        'status',
        'property_type'
    ];


    protected $casts = [
        
    ];

    public function reservation()
    {
        return $this->hasOne('App\Models\RealEstate\Reservation', 'client_number', 'client_number');
    }
}
