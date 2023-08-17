<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Seat;

class UpdateSeatStatus extends Controller
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
            'status' => $request->status,
        ]);

        return $seat;
    }
}
