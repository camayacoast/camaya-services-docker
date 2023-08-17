<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class GeneratedVoucher extends Model
{

    protected $connection = 'camaya_booking_db';
    protected $table = 'generated_vouchers';

    //
    protected $fillable = [
        'customer_id',
        'transaction_reference_number',
        'voucher_id',
        'voucher_code',
        'booking_reference_number',
        'guest_reference_number',

        'type',
        'description',
        'availability',
        
        'category',
        'mode_of_transportation',
        'allowed_days',
        'exclude_days',

        'price',

        'validity_start_date',
        'validity_end_date',

        'voucher_status',
        'used_at',

        'payment_status',
        'paid_at',

        'mode_of_payment',
        'payment_reference_number',
        'provider',
        'provider_reference_number',

        'created_by',

        'cancelled_at',
        'checkout_id'
    ];


    protected $casts = [
        // 'interfaces' => 'array',
        'exclude_days' => 'array', // Will converted to (Array)
        'allowed_days' => 'array', // Will converted to (Array)
    ];

    public function voucher()
    {
        return $this->hasOne('App\Models\Booking\Voucher', 'id', 'voucher_id');
    }

    public function created_by()
    {
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function customer()
    {
        return $this->hasOne('App\Models\Booking\Customer', 'id', 'customer_id');
    }
}
