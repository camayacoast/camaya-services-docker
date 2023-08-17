<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'packages';

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'availability',
        'category',
        'mode_of_transportation',
        'allowed_days',
        'exclude_days',
        // 'holidays',
        'selling_start_date',
        'selling_end_date',
        'booking_start_date',
        'booking_end_date',
        'status',
        'regular_price',
        'selling_price',
        'weekday_rate',
        'weekend_rate',
        'promo_rate',
        'walkin_price',
        'min_adult',
        'max_adult',
        'min_kid',
        'max_kid',
        'min_infant',
        'max_infant',
        'quantity_per_day',
        'stocks',
    ];

    protected $casts = [
        'exclude_days' => 'array', // Will convarted to (Array)
        'allowed_days' => 'array', // Will convarted to (Array)
        // 'holidays' => 'array', // Will convarted to (Array)
    ];

    /**
     * Relationships
     */
    public function allowedRoles()
    {
        return $this->hasMany('App\Models\Booking\PackageAllowRole', 'package_id', 'id');
    }

    public function allowedSources()
    {
        return $this->hasMany('App\Models\Booking\PackageAllowSource', 'package_id', 'id');
    }

    public function packageInclusions()
    {
        return $this->hasMany('App\Models\Booking\PackageInclusion', 'package_id', 'id')->where('type', 'product');
    }

    public function packageRoomTypeInclusions()
    {
        return $this->hasMany('App\Models\Booking\PackageInclusion', 'package_id', 'id')->where('type', 'room_type');
    }

    public function images()
    {
        return $this->hasMany('App\Models\Booking\PackageImage', 'package_id', 'id')->orderBy('cover', 'asc');
    }
}
