<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user()->user_type == 'admin') {
            return true;
        } else {
            if ($this->user()->hasPermissionTo('Booking.Cancel.Booking')) {
                return true;
            }
        }
        return false;
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
            'reference_number' => 'required'
        ];
    }
}
