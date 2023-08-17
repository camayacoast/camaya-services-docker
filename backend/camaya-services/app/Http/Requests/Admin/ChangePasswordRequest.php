<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // return false;
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
            // 'email'=> 'required|string|email|max:255|unique:users',
            // 'first_name'=> "required",
            // 'last_name'=> "required",

            'old_password'=> "required|min:6",
            'new_password'=> "required|min:6",
            // 'role'=> "required",
        ];
    }
}
