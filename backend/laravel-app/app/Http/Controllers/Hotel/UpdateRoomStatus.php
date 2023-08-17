<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\Room;

class UpdateRoomStatus extends Controller
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

        $roomToUpdate = Room::find($request->id);
        $roomToUpdate->update(['enabled' => $request->enabled]);

        $room = Room::where('rooms.id', $request->id)
                ->join('room_types', 'room_types.id', '=', 'rooms.room_type_id')
                ->select('rooms.*', 'room_types.name', 'room_types.code');        

        return response()->json($room->first(), 200);

    }
}
