<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientComembers extends Model
{
    //
    protected $table = "client_comembers";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'relationship',
        'birthdate',
    ];
}
