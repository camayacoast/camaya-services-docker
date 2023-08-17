<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

use Carbon\Carbon;

class CreateVoucherRequest extends FormRequest
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
            'name' => 'required',
            'code' => 'required|unique:camaya_booking_db.vouchers',
            // 'exclude_days' => 'distinct|array|regex:/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/',
            'exclude_days.*' => 'distinct|date',
        ];
    }

}
