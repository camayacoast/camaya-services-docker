<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class SalesTeam extends Model
{
    //

    protected $fillable = [
        'parent_id',
        'owner_id',
        'name',
        'created_by',
        'deleted_by',
        'deleted_at',
    ];


    public function members()
    {
        return $this->hasMany('App\Models\RealEstate\SalesTeamMember', 'team_id', 'id')->where('role', '!=', 'owner');
    }

    public function owner()
    {
        return $this->hasOne('App\Models\RealEstate\SalesTeamMember', 'team_id', 'id')->where('role', 'owner');
    }

    public function teamowner()
    {
        return $this->hasOne('App\User', 'id', 'owner_id');
    }

    public function sub_teams()
    {
        return $this->hasMany('App\Models\RealEstate\SalesTeam', 'parent_id', 'id')->whereNotNull('parent_id')->orderBy('id', 'ASC');
    }

}
