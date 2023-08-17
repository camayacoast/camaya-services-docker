<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->user_type == 'admin' || $this->user()->hasRole('super-admin');
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
            'permission' => 'required|regex:/^[a-z]*(?:\.[a-z\d*]+)*$/i|unique:permissions,name',
        ];
    }

    public function messages()
    {
        return [
            'unique' => "Permission already exists.",
            'regex' => "Module must contain letters only or invalid format."
        ];
    }

    protected function failedAuthorization()
    {
        throw new AuthorizationException('Unauthorized');

        // return "Unauthorized";
    }
}
