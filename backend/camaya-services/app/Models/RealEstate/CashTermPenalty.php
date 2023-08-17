<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class CashTermPenalty extends Model
{
    //

    protected $fillable = [
        'reservation_number',
        'cash_term_ledger_id',
        'penalty_amount',
        'type',
        'number',
        'paid_at',
        'amount_paid',
        'system_generated',
        'discount',
        'remarks'
    ];

    public function cash_term_ledger()
    {
        return $this->hasOne('App\Models\RealEstate\CashTermLedger', 'id', 'cash_term_ledger_id');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\RealEstate\RealEstatePayment', 'cash_term_ledger_id', 'id');
    }

}
