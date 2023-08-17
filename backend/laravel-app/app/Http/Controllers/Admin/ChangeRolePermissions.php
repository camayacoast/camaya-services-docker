<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\ChangeRolePermissionsRequest;

use Spatie\Permission\Models\Role;

class ChangeRolePermissions extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * 
     */
    public function __invoke(ChangeRolePermissionsRequest $request)
    {
        //

        $role = Role::findByName(Str::of($request->role)->replace('+', ' '), 'web');

        if (!$role) {
            return 'Role not found!';
        }

        if ($request->allowed) {
            $role->givePermissionTo($request->permission);
        } else {
            $role->revokePermissionTo($request->permission);
        }

        return response()->json(['allowed' => $request->allowed, 'permission' => $request->permission], 200);

    }
}
