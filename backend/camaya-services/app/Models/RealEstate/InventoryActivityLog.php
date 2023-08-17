<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class InventoryActivityLog extends Model
{
    protected $fillable = [
        'type',
        'action',
        'description',
        'details',
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
