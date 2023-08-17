<?php

namespace App\Http\Requests\RealEstate;

use Illuminate\Foundation\Http\FormRequest;

class OnlinePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
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
            'first_name' => 'required',
            // 'middle_name' => 'required',
            'last_name' => 'required',
            'email_address' => 'required|email',
            'contact_number' => 'required',
            'sales' => 'required',
            'sales_manager' => 'required',
            'amount' => 'required|numeric|between:10,1000000',
            // 'gateway' => 'required',
            // 'payment_channel' => 'required',
            // 'payment_gateway_reference_number' => 'required',
            // 'remarks' => 'required',
        ];
    }
}
