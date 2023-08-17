<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;

class UpdateUserLastName extends Controller
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
        if (!$request->user()->hasRole(['admin', 'IT', 'BPO', 'Sales Admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('Main.EditName.User')
            ) {
                return response()->json(['error' => 'NOT_ADMIN', 'message' => 'You are not authorized to edit user details.'], 400);
            }
        }

        $user = User::find($request->id);

        if (!$user) {
            return response()->json(['error' => 'USER_NOT_FOUND', 'message' => 'User not found'], 400);
        }


        $user->update([
            'last_name' => $request->last_name
        ]);
        
        // return response()->json($user, 200);
        return $user->refresh();
    }
}
