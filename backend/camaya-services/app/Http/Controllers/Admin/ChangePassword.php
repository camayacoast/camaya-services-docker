<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Admin\ChangePasswordRequest;

use Illuminate\Support\Facades\Hash;
use App\User;

class ChangePassword extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ChangePasswordRequest $request)
    {
        //

        // return $request->all();

        $isOldPasswordCorrect = Hash::check($request->old_password, $request->user()->password);

        if (!$isOldPasswordCorrect) {
            return response()->json([
                'error' => 'OLD_PASSWORD_INCORRECT',
                'message' => 'Old password incorrect',
            ], 400);
        }


        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        if (!$user->save()) {
            return response()->json([
                'error' => 'CHANGE_PASSWORD_FAILED',
                'message' => 'Failed to change password',
            ], 400);
        }

        return response()->json([
            'status' => 'NEW_PASSWORD_CHANGED',
            'message' => 'Password has been updated.',
        ], 200);
        
    }
}
