<?php

namespace App\Http\Requests\RealEstate;

use Illuminate\Foundation\Http\FormRequest;

class PaymentListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->hasPermissionTo('RealEstate.View.PaymentList') || $this->user()->hasRole('super-admin');
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
        ];
    }
}
