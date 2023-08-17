<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Seat;

class UpdateSeatAutoCheckInStatus extends Controller
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
        $seat = Seat::find($request->id);

        if (!$seat) {
            return response()->json(['error' => 'SEAT_NOT_FOUND'], 400);
        }

        $seat->update([
            'auto_check_in_status' => $request->auto_check_in_status,
        ]);

        return $seat;
    }
}
