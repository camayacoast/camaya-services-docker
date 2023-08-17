<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;

class UpdateUserRole extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('Main.EditRole.User')
            ) {
                return response()->json(['error' => 'NOT_ADMIN', 'message' => 'You are not admin.'], 400);
            }
        }

        // if ($request->user()->user_type != 'admin') {
        //     return response()->json(['error' => 'NOT_ADMIN', 'message' => 'You are not admin.'], 400);
        // }
        // return $request->all();
        $user = User::find($request->id);

        if (!$user) {
            return response()->json(['error' => 'USER_NOT_FOUND', 'message' => 'User not found'], 400);
        }

        // This will remove all roles and add the new one
        $user->syncRoles([$request->role]);
        

        return response()->json($user, 200);
    }
}
