<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\AdditionalEmail;
use App\Models\Booking\Booking;

class UpdateAdditionalEmails extends Controller
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

        if(!$booking) {
            return response()->json(['error' => 'BOOKING_NOT_FOUND'], 400);
        }

        //
        /**
         * Update additional emails
         */

         // Delete all booking additional emails
         AdditionalEmail::where('booking_id', $booking->id)->delete();


        if ($request->emails) {
            foreach ($request->emails as $email) {
                AdditionalEmail::create([
                    'booking_id' => $booking->id,
                    'email' => $email,
                    'created_by' => $request->user()->id,
                ]);
            }
        }

        return 'OK';

    }
}
