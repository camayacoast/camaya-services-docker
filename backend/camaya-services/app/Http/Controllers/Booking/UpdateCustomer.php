<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Customer;

class UpdateCustomer extends Controller
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

        $customer = Customer::find($request->id);

        if (!$customer) {
            return response()->json(['error' => 'CUSTOMER_NOT_FOUND'], 400);
        }

        if (Customer::where('id', '!=', $request->id)->where('email', '=', $request->email)->count()) {
            return response()->json(['error' => 'CUSTOMER_EMAIL_EXIST'], 400);
        }

        $customer->update([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'nationality' => $request->nationality,
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'email' => $request->email,
        ]);

        return Customer::where('id', $request->id)->with('user')->with('emailMatch')->withCount('bookings')->first();
    }
}
