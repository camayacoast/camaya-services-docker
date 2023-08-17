<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientProperties extends Model
{
    //
    protected $table = "client_properties";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'client_number',
        'area',
        'lot_number',
        'block_number',
        'subdivision',
    ];
}
