<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;
use App\Models\Booking\Customer;

class UpdatePrimaryGuest extends Controller
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

        $booking = Booking::where('reference_number', $request->booking_reference_number)->first();

        if (!$booking) {
            return response()->json(['error' => 'BOOKING_NOT_FOUND', 'message' => 'Booking not found!'], 400);
        }

        $updateBooking = Booking::where('reference_number', $request->booking_reference_number)
                ->update([
                    'customer_id' => $request->primary_customer_id
                ]);

        // Log action here

        if (!$updateBooking) {
            return response()->json(['error' => 'PRIMARY_CUSTOMER_FAILED_TO_UPDATE', 'message' => 'Failed to update primary customer!'], 400);
        }

        return Customer::where('id', $request->primary_customer_id)->first();

    }
}
