<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class RealEstatePaymentStatus extends Model
{
    //
    protected $fillable = [
        'transaction_id',
        'status',
        'message',
    ];
}
