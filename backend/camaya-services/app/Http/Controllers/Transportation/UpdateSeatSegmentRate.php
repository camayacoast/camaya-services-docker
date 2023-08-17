<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\SeatSegment;

class UpdateSeatSegmentRate extends Controller
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

        $seat_segment = SeatSegment::find($request->id);

        if (!$seat_segment) {
            return response()->json(['error' => 'SEAT_SEGMENT_NOT_FOUND'], 400);
        }

        $seat_segment->update([
            'rate' => $request->rate,
        ]);

        return $seat_segment;
    }
}
