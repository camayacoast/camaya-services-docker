<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;

class UpdateUserType extends Controller
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
        // return $request->all();

        $user = User::find($request->id);

        if (!$user) {
            return response()->json(['error' => 'USER_NOT_FOUND', 'message' => 'User not found'], 400);
        }


        $user->update([
            'user_type' => $request->user_type
        ]);
        

        return response()->json($user, 200);
    }
}
