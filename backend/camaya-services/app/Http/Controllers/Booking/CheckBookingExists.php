<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;

class CheckBookingExists extends Controller
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

        if (!$request->booking_reference_number) {
            return response()->json(['exists' => false], 200);
            // return 'No booking reference number indicated.';
        }

        $exists = Booking::where('reference_number', $request->booking_reference_number)->exists();

        return response()->json(['exists' => $exists], 200);
    }
}
