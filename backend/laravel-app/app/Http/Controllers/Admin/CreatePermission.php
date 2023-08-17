<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CreatePermissionRequest;

use Spatie\Permission\Models\Permission;

class CreatePermission extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreatePermissionRequest $request)
    {
        $permission_array = explode('.', $request->permission);

        $permission_string = [];

        foreach ($permission_array as $string) {
            $permission_string[] = ucfirst($string);
        }

        //
        $permission = Permission::create(['name' => implode('.', $permission_string), 'guard_name' => 'web']);

        if (!$permission) {
            return response()->json(['error' => 'Failed to create permission!'], 400);
        }

        return response()->json(['permission' => $permission_string], 200);
    }
}
