<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Customer;
use App\Http\Requests\Booking\CreateCustomerRequest;
use App\User;
use DB;
use Illuminate\Support\Facades\Hash;
use App\Mail\AgentCreated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class CreateCustomer extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreateCustomerRequest $request)
    {
        //
        $booking_db = DB::connection('camaya_booking_db');
        $services_db = DB::connection('mysql');

        $booking_db->beginTransaction();
        $services_db->beginTransaction();

        $user = User::where('email', $request->email)->first();

        $newCustomer = Customer::create([
            'object_id' => $user ? $user->object_id : null,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'nationality' => $request->nationality,
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'email' => $request->email,
            'created_by' => $request->user()->id,
        ]);

        if (!$newCustomer) {
            $booking_db->rollBack();
            $services_db->rollBack();
            return response()->json(['error' => 'Failed to create customer'], 200);
        } else {
            if ($request->isAgent == true) {
                $password = Str::random(7);
    
                $newUser = User::create([
                    'object_id' => (string) Str::orderedUuid(),
                    'email' => $request->email,
                    'password' => Hash::make($password),
                    'first_name' => $request['first_name'],
                    'last_name' => $request['last_name'],
                    'middle_name' => $request['middle_name'],
                    'user_type' => 'agent',
                    'email_verified_at' => Carbon::now(),
                ]);

                // Checks if property-consultant role exists, creates new record if not
                $role = Role::where('name', 'Property Consultant')->first();
                if (!$role) {
                    $role = Role::create(['name' => 'Property Consultant', 'guard_name' => 'web']);
                }
                $newUser->assignRole($role);

                if (!$newUser) {
                    $booking_db->rollBack();
                    $services_db->rollBack();
                    return response()->json(['error' => 'Failed to create customer'], 200);
                }

                // Assign role to agent
                // $newUser->assignRole($request['role']);

                // Update customer object
                $newCustomer->refresh();
                $newCustomer->update(['object_id' => $newUser->object_id]);

                /*
                * Send email confirmation to user
                */
                Mail::to($request->email)->send(new AgentCreated($newUser, $password));
            }
        }

        $booking_db->commit();
        $services_db->commit();

        return $newCustomer;
    }
}
