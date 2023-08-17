<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Seat;

class UpdateSeatOrder extends Controller
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

        $seat = Seat::find($request->id);

        if (!$seat) {
            return response()->json(['error' => 'SEAT_NOT_FOUND'], 400);
        }

        $seat->update([
            'order' => $request->order,
        ]);

        return $seat;
    }
}
