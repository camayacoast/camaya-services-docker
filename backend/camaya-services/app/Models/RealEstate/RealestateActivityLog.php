<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class RealestateActivityLog extends Model
{
    protected $fillable = [
        'reservation_number',
        'action',
        'description',
        'model',
        'properties',
        'created_by',
        'created_at',
    ];

    public static $paymentType = [
        'reservation_fee_payment' => 'Reservation',
        'downpayment' => 'Downpayment',
        'monthly_amortization_payment' => 'Monthly Amortization',
        'title_fee' => 'Title Transfer Fee',
        'retention_fee' => 'Retention Fee',
        'full_cash' => 'Full Cash',
        'partial_cash' => 'Partial Cash',
        'split_cash' => 'Split Cash',
        'penalty' => 'Penalty',
    ];

    /**
     *  Causer
     */
    public function causer()
    {
        return $this->belongsTo('App\User', 'created_by', 'id');
    }
}
