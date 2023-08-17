<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    //
    protected $table = "users";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'object_id',
        'first_name',
        'middle_name',
        'last_name',
        'user_type',
        'email',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('clients', function (Builder $builder) {
            $builder->where('user_type', 'client');
        });
    }

    public function information()
    {
        return $this->hasOne('App\Models\RealEstate\ClientInformation', 'user_id', 'id');
    }

    public function spouse()
    {
        return $this->hasOne('App\Models\RealEstate\ClientSpouse', 'client_id', 'id');
    }


    public function agent()
    {
        return $this->hasOne('App\Models\RealEstate\SalesClient', 'client_id', 'id');
    }
}
