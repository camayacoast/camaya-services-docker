<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // return $this->user()->user_type == 'admin' || $this->user()->hasRole(['super-admin']);
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
            // Account
            'email'=> 'required|string|email|max:255|unique:users',
            'first_name'=> "required",
            'last_name'=> "required",

            'user_type'=> "required",
            'role'=> "required",
            // 'password'=> "required|confirmed",
        ];
    }
}
