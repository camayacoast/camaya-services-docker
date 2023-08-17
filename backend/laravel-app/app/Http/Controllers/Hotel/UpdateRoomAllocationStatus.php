<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomAllocation;
use Carbon\Carbon;

class UpdateRoomAllocationStatus extends Controller
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

        $room_allocation = RoomAllocation::find($request->id);

        if (!$room_allocation) {
            return response()->json(['error' => 'ROOM_ALLOCATION_NOT_FOUND', 'message' => 'Room allocation does not exist.'], 400);
        }

        $room_allocation->status = $request->new_status;
        $room_allocation->updated_at = Carbon::now();
        $room_allocation->updated_by = $request->user()->id;

        if (!$room_allocation->save()) {
            return response()->json(['error' => 'ROOM_ALLOCATION_CHANGE_STATUS_FAILED', 'message' => 'Failed to update room allocation status.'], 400);
        }

        return response()->json($room_allocation->load('room_type.property'), 200);
    }
}
