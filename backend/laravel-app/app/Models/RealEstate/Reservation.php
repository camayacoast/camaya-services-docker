<?php

namespace App\Models\RealEstate;

use Illuminate\Database\Eloquent\Model;
use App\Models\RealEstate\LotInventory;

class Reservation extends Model
{
    //
    protected $fillable = [
        'client_id', 
        'agent_id',
        'sales_manager_id',
        'sales_director_id',

        'referrer_id',
        'referrer_property',

        'status',
        'client_number',
        'reservation_number',
        'remarks',
        'promo_type',
        'reservation_date',
        //

        'source',

        // Property
        'property_type',
        'subdivision',
        'block',
        'lot',
        'type',
        'area',
        'price_per_sqm',
        'total_selling_price',

        // Payments terms
        'reservation_fee_date',
        'reservation_fee_amount',
        'payment_terms_type', // cash / in_house_assisted_financing

        // common
        'discount_amount',
        'with_twelve_percent_vat',

        // cash
        'with_five_percent_retention_fee',
        'split_cash',
        'number_of_cash_splits',
        'split_cash_start_date',
        'split_cash_end_date',

        // in_house_assisted_financing
        'downpayment_amount',
        'downpayment_due_date',
        'number_of_years',
        'factor_rate',
        'monthly_amortization_due_date',
        'split_downpayment',
        'number_of_downpayment_splits',
        'split_downpayment_start_date',
        'split_downpayment_end_date',
        'interest_rate',
        'old_reservation'
    ];

    protected $appends = [
        'total_selling_price',
        'discount_percentage',
        'net_selling_price',
        'twelve_percent_vat',
        'net_selling_price_with_vat',

        // Cash
        'retention_fee',
        'total_amount_payable',
        'split_payment_amount',

        // In-house
        'total_balance_in_house',
        'monthly_amortization',
        'split_downpayment_amount',
        'downpayment_amount_less_RF',
        'subdivision_name',
    ];

    public function getSubdivisionNameAttribute()
    {
        $lots = LotInventory::where('subdivision', $this->subdivision)->first();
        return  !empty($lots) ? $lots['subdivision_name'] : '';
    }

    public function getTotalSellingPriceAttribute()
    {
        $tsp_final = $this->area * $this->price_per_sqm;
        if( $this->payment_terms_type == 'cash' ) {
            $tsp_rounded = round($this->area * $this->price_per_sqm);
            $tsp_final = ($tsp_rounded % 10 > 0) ? ($tsp_rounded - ($tsp_rounded % 10)) : $tsp_rounded;
        }
        return $tsp_final;
    }

    public function getDiscountPercentageAttribute()
    {
        if (!$this->total_selling_price) return 0;

        $tsp_final = $this->total_selling_price;
        if( $this->payment_terms_type == 'cash' ) {
            $tsp_rounded = round($this->total_selling_price);
            $tsp_final = ($tsp_rounded % 10 > 0) ? ($tsp_rounded - ($tsp_rounded % 10)) : $tsp_rounded;
        }

        return number_format(($this->discount_amount / $tsp_final) * 100, 2);
    }

    // const net_selling_price = totalSellingPrice - discountAmount;
    public function getNetSellingPriceAttribute()
    {

        $tsp_final = $this->total_selling_price;
        if( $this->payment_terms_type == 'cash' ) {
            $tsp_rounded = round($this->total_selling_price);
            $tsp_final = ($tsp_rounded % 10 > 0) ? ($tsp_rounded - ($tsp_rounded % 10)) : $tsp_rounded;
        }


        return ($tsp_final - $this->discount_amount);
    }

    // const twelve_percent_vat = net_selling_price * .12;
    public function getTwelvePercentVatAttribute()
    {
        return round($this->net_selling_price * .12, 2);
    }

    // const nsp_with_vat = net_selling_price + twelve_percent_vat;
    public function getNetSellingPriceWithVatAttribute()
    {
        return ($this->net_selling_price + $this->twelve_percent_vat);
    }

    // const nsp_computed = (withTwelvePercentVAT ? nsp_with_vat : net_selling_price);

    /**
     * CASH
     */
    // const retention_fee = nsp_computed * .05;
    public function getRetentionFeeAttribute()
    {
        if ($this->payment_terms_type != 'cash') return null;

        return ($this->with_twelve_percent_vat ? $this->net_selling_price_with_vat : $this->net_selling_price) * 0.05;
    }

    // const total_amount_payable = nsp_computed - ((withRetentionFee && tab == 'cash' ? retention_fee : 0) + reservationFeeAmount);
    public function getTotalAmountPayableAttribute()
    {
        if ($this->payment_terms_type != 'cash') return null;

        return ($this->with_twelve_percent_vat ? $this->net_selling_price_with_vat : $this->net_selling_price) - ($this->with_five_percent_retention_fee ? ($this->retention_fee + $this->reservation_fee_amount) : $this->reservation_fee_amount);
    }

    public function getSplitPaymentAmountAttribute()
    {
        if (($this->payment_terms_type != 'cash' && $this->split_cash) || !$this->number_of_cash_splits) return null;

        return ($this->total_amount_payable / $this->number_of_cash_splits);
    }

    /**
     * IN-HOUSE ASSISTED FINANCING
     */

    //  const total_balance_in_house = nsp_computed - downpaymentAmount;
    public function getTotalBalanceInHouseAttribute()
    {
        if ($this->payment_terms_type != 'in_house') return null;

        return round(($this->with_twelve_percent_vat ? $this->net_selling_price_with_vat : $this->net_selling_price) - ($this->downpayment_amount > 0 ? $this->downpayment_amount : $this->reservation_fee_amount), 2);
    }

    //  const monthly_amortization = total_balance_in_house * parseFloat(factorRate.rate ?? 0);
    public function getMonthlyAmortizationAttribute()
    {
        if ($this->payment_terms_type != 'in_house') return null;

        return round($this->total_balance_in_house * $this->factor_rate, 2);
    }

    public function getDownpaymentAmountLessRFAttribute()
    {
        if (($this->payment_terms_type != 'in_house' && $this->split_downpayment)) return null;

        return ($this->downpayment_amount - $this->reservation_fee_amount);
    }

    public function getSplitDownpaymentAmountAttribute()
    {
        if (($this->payment_terms_type != 'in_house' && $this->split_downpayment) || !$this->number_of_downpayment_splits) return null;

        return (($this->downpayment_amount - $this->reservation_fee_amount) / $this->number_of_downpayment_splits);
    }


    public function client()
    {
        return $this->belongsTo('App\Models\RealEstate\Client', 'client_id', 'id');
    }

    public function agent()
    {
        return $this->belongsTo('App\User', 'agent_id', 'id');
    }

    public function sales_manager()
    {
        return $this->hasOne('App\User', 'id', 'sales_manager_id');
    }

    public function sales_director()
    {
        return $this->hasOne('App\User', 'id', 'sales_director_id');
    }

    public function co_buyers()
    {
        return $this->hasMany('App\Models\RealEstate\ReservationCoBuyer', 'reservation_id', 'id');
    }

    public function promos()
    {
        return $this->hasMany('App\Models\RealEstate\ReservationPromo', 'reservation_number', 'reservation_number');
    }

    public function attachments()
    {
        return $this->hasMany('App\Models\RealEstate\ReservationAttachment', 'reservation_number', 'reservation_number');
    }

    public function amortization_schedule()
    {
        return $this->hasMany('App\Models\RealEstate\AmortizationSchedule', 'reservation_number', 'reservation_number');
    }

    public function cash_term_ledger()
    {
        return $this->hasMany('App\Models\RealEstate\CashTermLedger', 'reservation_number', 'reservation_number');
    }

    public function referrer()
    {
        return $this->hasOne('App\User', 'id', 'referrer_id');
    }

    public function referrer_property_details()
    {
        return $this->hasOne('App\Models\RealEstate\Reservation', 'reservation_number', 'referrer_property');
    }

    public function amortization_fees()
    {
        return $this->hasMany('App\Models\RealEstate\AmortizationFee', 'reservation_number', 'reservation_number');
    }

    public function payment_details()
    {
        return $this->hasMany('App\Models\RealEstate\RealEstatePayment', 'client_number', 'client_number')
            ->with('paymentStatuses')
            ->with(['paymentAttachments' => function($q){
                $q->orderBy('created_at', 'desc');
            }]);
    }
}
