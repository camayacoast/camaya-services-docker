<?php

namespace App\Models\RealEstate;
use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Model;

class RealEstatePayment extends Model
{
    //

    public $user = false;

    protected $fillable = [
        'transaction_id',
        'client_id',
        'client_number',
        
        // New
        'amortization_schedule_id',
        'reservation_number',

        'first_name',
        'middle_name',
        'last_name',
        'email',
        'contact_number',
        'sales_agent',
        'sales_manager',
        'currency',
        'payment_amount',
        'paid_at',
        'payment_type',
        'payment_gateway',
        'payment_channel',
        'payment_encode_type',
        'payment_gateway_reference_number',
        'remarks',
        'or_number',
        'cr_number',
        'is_verified',
        'verified_date',
        'verified_by',
        'discount',
        'advance_payment',
        'cash_term_ledger_id',

        'bank',
        'bank_account_number',
        'check_number',
        'record_type',
    ];

    protected $appends = [
        'allowed_to_update_payment',
    ];

    public function paymentStatuses()
    {
        return $this->hasMany('App\Models\RealEstate\RealEstatePaymentStatus', 'transaction_id', 'transaction_id');
    }

    public function paymentAttachments()
    {
        return $this->hasMany('App\Models\RealEstate\PaymentDetailAttachment', 'transaction_id', 'transaction_id');
    }

    public function verifiedBy()
    {
        return $this->hasOne('App\User', 'id', 'verified_by');
    }

    public function reservation()
    {
        return $this->hasOne('App\Models\RealEstate\Reservation', 'client_number', 'client_number'); 
    }

    public function getAllowedToUpdatePaymentAttribute()
    {
        $is_allowed = true;
        if (!request()->user()->hasRole(['super-admin'])) {
            if ( !request()->user()->hasPermissionTo('SalesAdminPortal.UpdatePayment.AmortizationLedger') ) {
                $is_allowed = false;
            }
        }

        return $is_allowed;
    }
}
