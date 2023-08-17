<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

use Spatie\Permission\Models\Role;

class Product extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'products';

    protected $fillable = [
        'name',
        'code',
        'type',
        'category',
        'availability',
        'status',
        'serving_time',
        'allowed_days',
        'quantity_per_day',
        'price',
        'walkin_price',
        'kid_price',
        'infant_price',
        'description',
        'auto_include',
        'addon_of',
    ];

    protected $casts = [
        'allowed_days' => 'array', // Will be converted to (Array)
        'serving_time' => 'array', // Will be converted to (Array)
    ];

    /**
     * Relationships
     */
    public function allowedRoles()
    {
        return $this->hasMany('App\Models\Booking\ProductAllowRole', 'product_id', 'id');
            // ->join(env('DB_DATABASE').'.roles', 'product_allow_roles.role_id', '=', 'roles.id')
            // ->select('product_allow_roles.id', 'role_id', 'product_id', 'roles.name');
    }

    public function allowedSources()
    {
        return $this->hasMany('App\Models\Booking\ProductAllowSource', 'product_id', 'id');
    }

    public function images()
    {
        return $this->hasMany('App\Models\Booking\ProductImage', 'product_id', 'id')->orderBy('cover', 'asc');
    }

    public function productPass()
    {
        return $this->hasMany('App\Models\Booking\ProductPass', 'product_id', 'id');
    }

}
