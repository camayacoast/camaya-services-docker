<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\ResetPassword;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPassword as ResetPasswordMail;
use App\Mail\GolfResetPassword as GolfResetPasswordMail;
use App\Mail\ChangePasswordSuccessful as ChangePasswordSuccessfulMail;
use App\Mail\GolfChangePasswordSuccessful as GolfChangePasswordSuccessfulMail;
use Validator;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    //
    public function resetPassword(Request $request)
    {
        // return $request->all();

        $user = User::where('email', $request->email)->first();

        /**
         * Check if email exists
         */
        if (!$user) {
            return response()->json(['error' => 'user_not_found', 'message' => 'E-mail address not recognized.'], 400);
        }

        /**
         * Create reset password code
         */

        $reset_password_code = str_pad(rand(1,999999), 6, '0', STR_PAD_LEFT);

        $createResetPassword = $user->reset_password()->updateOrCreate(
            [
                'email' => $user->email,
            ],
            [
                'token' => $reset_password_code,
                'expiry' => null,
            ]
        );

        

        if (!$createResetPassword) {
            return response()->json(['error' => 'unable_to_reset_password', 'message' => 'Sorry, you can not change your password right now.'], 400);
        }

        /**
         * Email the user with the code
         */
        if ($request->golf) {
            Mail::to($user->email)->send(new GolfResetPasswordMail($user, env('CAMAYA_GOLF_PORTAL') ."/"."change-password/".$user->email."/".$reset_password_code));
        } else {
            Mail::to($user->email)->send(new ResetPasswordMail($user, env('CAMAYA_PAYMENT_PORTAL') ."/"."change-password/".$user->email."/".$reset_password_code));
        }

        return response()->json(['email' => $user->email, 'message' => ''], 200);
    }

    public function changePassword(Request $request)
    {
        // return $request->all();
        /**
         * Check if email and code matches the record
         */

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'code' => 'required',
            'password'=> "required|confirmed",
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'paramaters_missing', 'message' => 'Some information missing.'], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->reset_password['token'] != $request->code) {
            return response()->json(['error' => 'incorrect_information', 'message' => 'Incorrect information.'], 400);
        }

        if (!$user->email == $request->email || !$user->reset_password->token == $request->code) {
            return response()->json(['error' => 'paramaters_missing', 'message' => 'Incorrect information.'], 400);
        }

        $user->where('id', $user->id)->update([
            'password' => Hash::make($request['password']),
        ]);

        if (!$user) {
            return response()->json(['error' => 'change_password_failed', 'message' => 'Password Reset'], 400);
        }

        ResetPassword::where('email', $user->email)->delete();

        if ($request->golf) {
            Mail::to($user->email)->send(new GolfChangePasswordSuccessfulMail($user));
        } else {
            Mail::to($user->email)->send(new ChangePasswordSuccessfulMail($user));
        }
        

        return 'OK';

    }
}
