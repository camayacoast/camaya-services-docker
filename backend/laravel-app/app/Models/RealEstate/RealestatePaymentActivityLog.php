<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class RealestatePaymentActivityLog extends Model
{
    protected $fillable = [
        'action',
        'description',
        'model',
        'properties',
        'created_by',
        'created_at',
    ];

    /**
     *  Causer
     */
    public function causer()
    {
        return $this->belongsTo('App\User', 'created_by', 'id');
    }
}
