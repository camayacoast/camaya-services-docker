<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class PackageAllowRole extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'package_allow_roles';

    protected $fillable = [
        'package_id',
        'role_id',
    ];

    public function role()
    {
        return $this->hasOne('App\Models\Main\Role', 'id', 'role_id')
                ->select('id','name');
    }
}
