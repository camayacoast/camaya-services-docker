<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class ProductPass extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'product_passes';

    protected $fillable = [
        'product_id',
        'stub_id',
    ];


    public function product()
    {
        return $this->belongsTo('App\Models\Booking\Product', 'product_id', 'id');
    }

    public function stub()
    {
        return $this->hasOne('App\Models\Booking\Stub', 'id', 'stub_id');
    }
}
