<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->user_type == 'admin';
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
            // 'object_id' => '',
            'first_name' => 'required',
            // 'middle_name' => '',
            'last_name' => 'required',
            // 'nationality' => '',
            'contact_number' => '',
            // 'address' => 'required',
            'email'=> 'required|string|email|max:255|unique:camaya_booking_db.customers',
            'created_by' => '',
        ];
    }
}
