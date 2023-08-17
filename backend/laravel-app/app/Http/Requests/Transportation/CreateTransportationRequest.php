<?php

namespace App\Http\Requests\Transportation;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransportationRequest extends FormRequest
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
            'code' => 'required',
            'type' => 'required',
            'mode' => 'required',
            // 'description' => 'required',
            'capacity' => 'required|integer|min:1',
            'max_infant' => 'integer|min:0',
            // 'status' => 'required',
            // 'current_location' => 'required',
        ];
    }
}
