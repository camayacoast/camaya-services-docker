<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomType;

class UpdateRoomTypeStatus extends Controller
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
        $roomToUpdate = RoomType::find($request->id);
        $roomToUpdate->update(['status' => $request->status]);

        $room = RoomType::where('id', $request->id)
                ->withCount('enabledRooms');    

        return response()->json($room->first(), 200);
    }
}
