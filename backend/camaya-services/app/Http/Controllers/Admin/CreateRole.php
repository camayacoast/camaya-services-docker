<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CreateRoleRequest;

use Spatie\Permission\Models\Role;

class CreateRole extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreateRoleRequest $request)
    {
        //
        $role = Role::create(['name' => $request->role, 'guard_name' => 'web']);

        if (!$role) {
            return response()->json(['error' => 'Failed to create role!'], 400);
        }

        return response()->json(['role' => $request->role], 200);
    }
}
