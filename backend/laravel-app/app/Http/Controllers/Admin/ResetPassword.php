<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Mail\Admin\ResetUserPassword;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

use App\User;

class ResetPassword extends Controller
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

        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json(['error' => 'USER_NOT_FOUND'], 404);
        }

        $new_password = Str::random(7);
        $hashed_password = Hash::make($new_password);

        $user->update([
            'password' => $hashed_password,
        ]);

        /*
         * Send email confirmation to user
         */
        Mail::to($user->email)->send(new ResetUserPassword($user, $new_password));
        
        return "OK";
    }
}
