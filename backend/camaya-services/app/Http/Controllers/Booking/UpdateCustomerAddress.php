<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Customer;

class UpdateCustomerAddress extends Controller
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

        $customer->update([
            'address' => $request->address
        ]);

        return Customer::where('id', $request->id)->with('user')->with('emailMatch')->withCount('bookings')->first();
    }
}
