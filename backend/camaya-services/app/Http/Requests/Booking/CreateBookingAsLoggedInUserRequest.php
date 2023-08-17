<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingAsLoggedInUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return in_array($this->user()->user_type, ['customer', 'client', 'employee', 'agent']);
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
            'customer' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'contact_number' => 'required',
            'email'=> 'required|string|email|max:255',
            'address' => 'required',

            'date_of_visit' => 'required|array|min:2',
            'adult_pax' => 'required',
            // 'kid_pax' => 'required',
            // 'infant_pax' => 'required',
            //// 'mode_of_transportation' => 'required',
            ////'eta' => 'required',
            // 'pay_until' => 'required',
            // 'auto_cancel_at' => 'required',
            // 'label' => 'required',
            // 'tags.*' => 'distinct',
            // 'source' => 'required',
            // 'remarks' => 'required',
            'additional_emails.*' => 'email',
            'adult_guests.*.first_name' => 'required',
            'adult_guests.*.last_name' => 'required',
            'adult_guests.*.age' => 'required|integer|between:0,100',
            //'adult_guests.*.nationality' => 'required',

            'kid_guests.*.first_name' => 'required',
            'kid_guests.*.last_name' => 'required',
            'kid_guests.*.age' => 'required|integer|between:3,12',
            //'kid_guests.*.nationality' => 'required',

            'infant_guests.*.first_name' => 'required',
            'infant_guests.*.last_name' => 'required',
            'infant_guests.*.age' => 'required|integer|between:0,2',

            'guest_vehicles.*.vehicle_model' => 'required',
            'guest_vehicles.*.vehicle_plate_number' => 'required',
            //'infant_guests.*.nationality' => 'required',
        ];
    }
}
