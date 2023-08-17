<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    //
    protected $connection = 'camaya_booking_db';
    protected $table = 'payments';

    protected $fillable = [
        'booking_reference_number',
        'invoice_id',
        'folio_id',
        'inclusion_id',
        'voucher_id',
        'billing_instruction_id',

        'payment_reference_number',
        'mode_of_payment',
        'market_segmentation',
        // 'charge_to',
        // 'type',
        'status',
        'provider',
        'provider_reference_number',
        'amount',
        'remarks',
        // 'billing_instructions',
        'paid_at',
        'voided_by',
        'voided_at',
        'refunded_by',
        'refunded_at',
        'updated_at',
        'created_by',
        'created_at',
    ];
}
