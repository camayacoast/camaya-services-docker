<?php

namespace App\Http\Requests\RealEstate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Access\AuthorizationException;

class AddPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // return false;
        if (!$this->user()->hasRole(['super-admin'])) {
            if ( 
                $this->user()->user_type != 'admin' ||
                !$this->user()->hasPermissionTo('SalesAdminPortal.AddPayment.AmortizationLedger')
            ) {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            // 'transaction_id' => 'required|unique:real_estate_payments',
            // 'client_id' => 'required',
            // 'client_number' => 'required',
            // 'first_name' => 'required',
            // 'middle_name' => 'required',
            // 'last_name' => 'required',
            // 'email_address' => 'required|email',
            // 'contact_number' => 'required',
            // 'sales' => 'required',
            // 'sales_manager' => 'required',
            'payment_gateway' => 'required',
            'payment_type' => 'required',
            'paid_at' => 'required',
            'payment_amount' => 'required|numeric|between:1,10000000',

            'reservation_number' => 'required',
            // 'gateway' => 'required',
            // 'payment_channel' => 'required',
            // 'payment_gateway_reference_number' => 'required',
            // 'remarks' => 'required',
        ];
    }

    protected function failedAuthorization()
    {
        throw new AuthorizationException('Unauthorized');

        // return "Unauthorized";
    }
}
