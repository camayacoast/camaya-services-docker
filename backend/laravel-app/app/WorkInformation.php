<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkInformation extends Model
{
    //

    protected $fillable = [
        'company_name',
        'business_telephone_number',
        'business_address',
        'industry',
    ];

    
}
