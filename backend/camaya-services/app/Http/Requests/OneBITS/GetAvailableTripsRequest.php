<?php

namespace App\Http\Requests\OneBITS;

use Illuminate\Foundation\Http\FormRequest;

class GetAvailableTripsRequest extends FormRequest
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
            'selected_route' => 'required',
            'selected_date' => 'required|date',
            'total_passengers.adult' => 'required|numeric|min:1',
        ];
    }
}
