<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomReservation;

class GetLastAvailableReservationDate extends Controller
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

        $room_reservation = RoomReservation::where('room_id', $request->room_id)
                                        ->where('start_datetime', '>', $request->start_date)
                                        ->orderBy('start_datetime', 'asc')
                                        ->whereNotIn('status', ['cancelled'])
                                        ->first();

        return $room_reservation;
    }
}
