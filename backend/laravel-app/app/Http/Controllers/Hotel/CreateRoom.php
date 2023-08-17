<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Hotel\CreateRoomRequest;
use App\Models\Hotel\Room;

class CreateRoom extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreateRoomRequest $request)
    {
        //
        // return $request->all();

        $exists = Room::where('property_id', $request->property_id)->where('number', $request->number)->exists();

        if ($exists) {
            return response()->json(['error' => 'Room number already exist.'], 400);
        }

        $newRoom = Room::create([
            'property_id' => $request->property_id,
            'room_type_id' => $request->room_type_id,
            'number' => $request->number,
            'description' => $request->description,
        ]);

        if (!$newRoom->save()) {
            return response()->json(['error' => 'Could not save room.'], 400);
        }

        $room = Room::where('rooms.id', $newRoom->id)
                ->join('room_types', 'room_types.id', '=', 'rooms.room_type_id')
                ->select('rooms.*', 'room_types.name', 'room_types.code');        

        return response()->json($room->first(), 200);

    }
}
