<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

use App\Models\Main\Role;

class ProductAllowRole extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'product_allow_roles';

    protected $fillable = [
        'product_id',
        'role_id',
    ];

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id')
                ->select('id','name');
    }
}
