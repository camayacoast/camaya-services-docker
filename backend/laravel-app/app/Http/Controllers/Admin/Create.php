<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\CreateAdminRequest;
use App\Mail\AdminCreated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

use App\Models\Booking\Customer;

class Create extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreateAdminRequest $request)
    {
        //
        DB::beginTransaction();

        /*
         * Create admin account
         */
        // email
        // first_name
        // middle_name
        // last_name

        // Creates auto password
        $password = Str::random(7);

        $newUser = User::create([
            'object_id' => (string) Str::orderedUuid(),
            'email' => $request['email'],
            'password' => Hash::make($password),
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'middle_name' => $request['middle_name'],
            'user_type' => $request['user_type'],
            'email_verified_at' => Carbon::now(),
        ]);

        $newUser->assignRole($request['role']);

        if ($request['user_type'] == 'customer') {
            Customer::firstOrCreate(
                ['email' => $request['email']],
                [
                'object_id' => $newUser->object_id,
                'first_name' => $request['first_name'],
                // 'middle_name' => $request->middle_name,
                'last_name' => $request['last_name'],
                'nationality' => $request->nationality ?? 'none',
                'contact_number' => $request->contact_number ?? 'none',
                'address' => $request->address,
                'email' => $request['email'],
                'created_by' => null,
            ]);
        }


        if (!$newUser) {
            DB::rollback();
            return response()->json(['error' => "Registration has failed."], 400);
        }

        DB::commit();

        
        /*
         * Send email confirmation to user
         */
        Mail::to($request->email)->send(new AdminCreated($newUser, $password));

        return $newUser;
    }
}
