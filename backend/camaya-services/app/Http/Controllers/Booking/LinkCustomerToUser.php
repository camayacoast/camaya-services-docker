<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Customer;
use App\User;

class LinkCustomerToUser extends Controller
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
        $customer = Customer::where('id', $request->id)->first();

        if (!$customer) {
            return response()->json(['error' => 'CUSTOMER_NOT_FOUND', 'message' => 'Customer not found.'], 400);
        }

        $user = User::where('email', $customer->email)->first();

        if (!$user) {
            return response()->json(['error' => 'NO_EMAIL_MATCH', 'message' => 'Customer email does not match to any records from user emails.'], 400);
        }

        Customer::where('id', $request->id)
            ->update([
                'object_id' => $user->object_id
            ]);

        $customer->refresh();

        return response()->json([
                'user' => $user,
                'customer' => $customer->load('user')->load('emailMatch')->withCount('bookings'),
                'message' => 'Customer linked to user.'
        ], 200);
    }
}
