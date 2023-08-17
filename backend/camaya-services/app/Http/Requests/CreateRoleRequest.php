<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoleRequest extends FormRequest
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
            'role'=> 'required|string|max:255|unique:roles,name',
        ];
    }

    protected function failedAuthorization()
    {
        throw new AuthorizationException('Unauthorized');

        // return "Unauthorized";
    }
}
