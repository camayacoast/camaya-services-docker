<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inclusion extends Model
{
    //
    use SoftDeletes;

    protected $connection = 'camaya_booking_db';
    protected $table = 'inclusions';

    protected $fillable = [
        'booking_reference_number',
        'invoice_id',
        'guest_id',
        'guest_reference_number',
        'parent_id',
        'item',
        'code',
        'type',
        'description',
        'serving_time',
        'used_at',
        'quantity',
        'original_price',
        'price',
        'walkin_price',
        'selling_price',
        'selling_price_type',
        'discount',
        'created_by',
        'deleted_by',
    ];

    public function guestInclusion()
    {
        return $this->hasOne('App\Models\Booking\Guest', 'id', 'guest_id');
    }

    public function packageInclusions()
    {
        return $this->hasMany('App\Models\Booking\Inclusion', 'parent_id', 'id');
    }

    public function deleted_by_user()
    {
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

    public function package()
    {
        return $this->hasOne('App\Models\Booking\Package', 'code', 'code');
    }

    public function product()
    {
        return $this->hasOne('App\Models\Booking\Product', 'code', 'code');
    }

    public function booking()
    {
        return $this->belongsTo('App\Models\Booking\Booking', 'booking_reference_number', 'reference_number');
    }

}
