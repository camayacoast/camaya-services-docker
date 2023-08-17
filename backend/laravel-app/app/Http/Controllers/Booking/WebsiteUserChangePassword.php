<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use App\Http\Requests\Booking\ChangePasswordRequest;
use Illuminate\Support\Facades\Hash;

use App\Mail\Booking\WebsiteChangePassword;
use Illuminate\Support\Facades\Mail;

class WebsiteUserChangePassword extends Controller
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

        if (!$request->user()) {
            return response()->json(['error' => 'USER_NOT_FOUND'], 400);
        }

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

        // Send the password update to email
        Mail::to($request->user()->email)
                ->send(new WebsiteChangePassword($request->user(), $request->new_password));

        return response()->json([
            'status' => 'NEW_PASSWORD_CHANGED',
            'message' => 'Password has been updated.',
        ], 200);


    }
}
