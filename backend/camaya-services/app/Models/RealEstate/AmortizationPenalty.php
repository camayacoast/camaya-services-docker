<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;

class AmortizationPenalty extends Model
{
    //

    protected $fillable = [
        'reservation_number',
        'amortization_schedule_id',
        'number',
        'is_old',
        
        'penalty_amount',
        'type',
        
        'paid_at',
        'amount_paid',
        'system_generated',
        'discount',

        'remarks'
    ];

    public function amortization_schedule()
    {
        return $this->hasOne('App\Models\RealEstate\AmortizationSchedule', 'id', 'amortization_schedule_id');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\RealEstate\RealEstatePayment', 'amortization_schedule_id', 'amortization_schedule_id');
    }

}
