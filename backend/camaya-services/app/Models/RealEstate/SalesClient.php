<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class SalesClient extends Model
{
    //
    protected $fillable = [
        'client_id',
        'sales_id',
        'created_by',
        'deleted_by',
        'deleted_at',
    ];

    public function client()
    {
        return $this->hasOne('App\Models\RealEstate\Client', 'client_id', 'id');
    }

    public function agent_details()
    {
        return $this->hasOne('App\User', 'id', 'sales_id');
    }
}
