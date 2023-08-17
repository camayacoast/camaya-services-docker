<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'vouchers';

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
        'selling_start_date',
        'selling_end_date',
        'booking_start_date',
        'booking_end_date',
        'status',
        'price',
        'quantity_per_day',
        'stocks',
    ];


    protected $casts = [
        // 'interfaces' => 'array',
        'exclude_days' => 'array', // Will converted to (Array)
        'allowed_days' => 'array', // Will converted to (Array)
    ];

    public function images()
    {
        return $this->hasMany('App\Models\Booking\VoucherImage', 'voucher_id', 'id')->orderBy('cover', 'asc');
    }
}
