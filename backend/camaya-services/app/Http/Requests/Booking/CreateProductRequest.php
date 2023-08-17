<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->user_type == 'admin' || $this->user()->hasPermission(['Booking.Add.Product']);
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
            'name' => 'required',
            'code' => 'required|unique:camaya_booking_db.products',
            'type' => 'required',
            'availability' => 'required',
            'serving_time' => 'array|min:1',
            // 'quantity_per_day' => 'required',
            'price' => 'required',
            // 'walkin_price' => 'required',
            // 'kid_price' => 'required',
            // 'infant_price' => 'required',
            // 'description' => 'required',
            // 'auto_include' => 'required',
            // 'addon_of' => 'required',
            // 'allowed_roles' => 'required',
            // 'allowed_sources' => 'required',
        ];
    }
}
