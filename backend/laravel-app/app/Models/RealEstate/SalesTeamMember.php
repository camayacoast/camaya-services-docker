<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class SalesTeamMember extends Model
{
    //
    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'created_by',
        'deleted_by',
        'deleted_at',
    ];

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function team()
    {
        return $this->belongsTo('App\Models\RealEstate\SalesTeam', 'team_id', 'id');
    }

    public function created_bookings()
    {
        return $this->hasMany('App\Models\Booking\Booking', 'created_by', 'user_id');
    }

}
