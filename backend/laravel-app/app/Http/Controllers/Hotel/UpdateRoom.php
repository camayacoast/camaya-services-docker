<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\Room;

class UpdateRoom extends Controller
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

        $exists = Room::where('property_id', $request->property_id)
            ->where('number', $request->number)
            ->where('id', '!=', $request->id)
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Room number already exist.'], 400);
        }

        $roomToUpdate = Room::find($request->id);
        $roomToUpdate->update([
            'description' => $request->description,
            'number' => $request->number,
            'fo_status' => $request->fo_status,
            'room_status' => $request->room_status,
            'room_type_id' => $request->room_type_id,
        ]);

        $room = Room::where('rooms.id', $request->id)
                ->join('room_types', 'room_types.id', '=', 'rooms.room_type_id')
                ->select('rooms.*', 'room_types.name', 'room_types.code');        

        return response()->json($room->first(), 200);
    }
}
