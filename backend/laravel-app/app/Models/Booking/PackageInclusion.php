<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class PackageInclusion extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'package_inclusions';

    protected $fillable = [
        'package_id',
        'related_id',
        'quantity',
        'type',
        'entity',
    ];

    public function product()
    {
        return $this->hasOne('App\Models\Booking\Product', 'id', 'related_id')->select('id', 'name', 'code', 'price', 'type');
    }

    public function room_type()
    {
        return $this->hasOne('App\Models\Hotel\RoomType', 'id', 'related_id')->select('id', 'name', 'code', 'rack_rate');
    }
}
