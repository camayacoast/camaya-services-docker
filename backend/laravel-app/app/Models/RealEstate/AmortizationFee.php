<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class AmortizationFee extends Model
{
    //

    protected $fillable = [
        'reservation_number',
        'amortization_schedule_id',
        
        'amount',
        'type',

        'payment_transaction_id',

        'remarks',

        'created_by',
    ];

    public function amortization_schedule()
    {
        return $this->hasOne('App\Models\RealEstate\AmortizationSchedule', 'id', 'amortization_schedule_id');
    }

    public function added_by()
    {
        return $this->hasOne('App\User', 'id', 'created_by');
    }
}
