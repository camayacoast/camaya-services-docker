<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class RealestatePromo extends Model
{
    
    protected $fillable = [
        'promo_type',
        'name',
        'description',
        'status',
    ];

}
