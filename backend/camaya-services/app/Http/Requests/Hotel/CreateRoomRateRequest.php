<?php

namespace App\Http\Requests\Hotel;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoomRateRequest extends FormRequest
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
            'description' => 'required',
            'room_types' => 'required|array',
            'room_rate' => 'required|integer|min:1',
            'date_range' => 'required|array|min:2',
            'exclude_days.*' => 'distinct|date',
        ];
    }
}
