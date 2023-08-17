<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\Room;

class UpdateHotelRoomStatus extends Controller
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

        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('Hotel.UpdateRoomStatus.Room')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $room = Room::find($request->room_id);

        if (!$room) {
            return response()->json(['error' => 'ROOM_NOT_FOUND'], 400);
        }

        $room->update([
            'room_status' => $request->room_status
        ]);

        return $room;
    }
}
