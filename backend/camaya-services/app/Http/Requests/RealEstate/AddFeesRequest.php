<?php

namespace App\Http\Requests\RealEstate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Access\AuthorizationException;

class AddFeesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!$this->user()->hasRole(['super-admin'])) {
            if ( 
                $this->user()->user_type != 'admin' ||
                !$this->user()->hasPermissionTo('SalesAdminPortal.AddFees.AmortizationLedger')
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
            'reservation_number' => 'required',
            'type' => 'required',
            'amount' => 'required|numeric|between:5000,10000000',
        ];
    }

    protected function failedAuthorization()
    {
        throw new AuthorizationException('Unauthorized');

        // return "Unauthorized";
    }
}
