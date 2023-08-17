<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\SeatSegment;

class UpdateSeatSegmentBookingTypes extends Controller
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

        $seat_segment = SeatSegment::find($request->id);

        if (!$seat_segment) {
            return response()->json(['error' => 'ERROR', 'message' => "Error"], 400);
        }

        $seat_segment->update(['booking_type' => $request->new_booking_types]);

        return $seat_segment;
    }
}
