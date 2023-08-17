<?php

namespace App\Http\Requests\Hotel;

use Illuminate\Foundation\Http\FormRequest;

class CreatePropertyRequest extends FormRequest
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
            'name' => 'required',
            'code' => 'required|unique:camaya_booking_db.properties',
            'type' => 'required',
            // 'address' => 'required',
            // 'phone_number' => 'required',
            // 'floors' => 'required',
            // 'cover_image_path' => 'required',
            // 'description' => 'required',
            'status' => 'required',
            
            'room_types.*.name' => 'required',
            'room_types.*.code' => 'required|unique:camaya_booking_db.room_types,code',
            'room_types.*.capacity' => 'required|integer',
            'room_types.*.rack_rate' => 'required|integer',
        ];
    }
}
