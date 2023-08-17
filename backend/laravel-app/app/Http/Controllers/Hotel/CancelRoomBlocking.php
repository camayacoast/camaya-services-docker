<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomReservation;

class CancelRoomBlocking extends Controller
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
        $roomReservation = RoomReservation::where('id', $request->id)->update([
            'status' => 'cancelled',
            'created_by' => $request->user()->id
        ]);

        return $roomReservation;
    }
}
