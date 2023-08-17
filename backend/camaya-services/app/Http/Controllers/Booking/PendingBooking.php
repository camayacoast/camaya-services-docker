<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Booking\PendingBookingRequest;
use App\Models\Booking\Booking;
use App\Models\Booking\Guest;

use Carbon\Carbon;

class PendingBooking extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(PendingBookingRequest $request)
    {
        //
        // return $request->all();
        $booking = Booking::where('reference_number', $request->reference_number)->first();

        if ($booking->status == 'cancelled') {
            if ($booking->mode_of_transportation != 'own_vehicle' && $booking->type != 'DT') {
                return response()->json(['error' => 'Failed to return to pending'], 400);
            }
        }

        $booking->update([
            'status' => 'pending',
            // 'approved_at' => Carbon::now(),
            // 'approved_by' => $request->user()->id
        ]);

        // Customer arrival status
        Guest::where('booking_reference_number', $booking->reference_number)
            ->update([
                'status' => 'arriving'
            ]);

        return $booking;
    }
}
