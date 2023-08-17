<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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

        $register = $this->route('register');

        return [
            
            // Account
            'email'=> 'required|string|email|max:255|unique:users',
            'first_name'=> "required",
            'last_name'=> "required",
            'password'=> "required|confirmed",

            // Profile
            'contact_number'=> "required",
            'prefix' => "required",

            'birth_date' => isset($register->non_hoa) && $register->non_hoa == true ? "required|date" : "",
            'birth_place' => isset($register->non_hoa) && $register->non_hoa == true ? "required|date" : "",
            'nationality' => isset($register->non_hoa) && $register->non_hoa == true ? "required|date" : "",
            'residence_address' => isset($register->non_hoa) && $register->non_hoa == true ? "required|date" : "",
            'photo' => isset($register->non_hoa) && $register->non_hoa == true ? "required|date" : "",
            'valid_id' => isset($register->non_hoa) && $register->non_hoa == true ? "required|date" : "",
            // telephone_number

            // Property details
            'area' => isset($register->non_hoa) && $register->non_hoa == true ? "required|numeric" : "",
            'block_number' => isset($register->non_hoa) && $register->non_hoa == true ? "required|alpha_num" : "",
            'client_number' => isset($register->non_hoa) && $register->non_hoa == true ? "required|alpha_num" : "",
            'lot_number' => isset($register->non_hoa) && $register->non_hoa == true ? "required|alpha_num" : "",
            'subdivision' => isset($register->non_hoa) && $register->non_hoa == true ? "required" : "",

            // Comembers
            'comembers.*.first_name'=> "required",
            'comembers.*.last_name'=> "required",
            'comembers.*.relationship'=> "required",
            'comembers.*.birthdate'=> "required",
        ];
    }
}
