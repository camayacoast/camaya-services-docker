<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Access\AuthorizationException;

class ChangeRolePermissionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->hasRole(['super-admin']) || $this->user()->hasPermissionTo('Main.Edit.Permission');
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
            'role' => 'required',
        ];
    }

    protected function failedAuthorization()
    {
        throw new AuthorizationException('Unauthorized');

        // return "Unauthorized";
    }

}
