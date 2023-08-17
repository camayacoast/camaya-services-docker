<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomAllocation;

class UpdateRoomAllocationAllowedRoles extends Controller
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
            return response()->json(['error' => 'ERROR', 'message' => "Error"], 400);
        }

        $room_allocation->update(['allowed_roles' => $request->new_roles]);

        return $room_allocation->load('room_type.property');
    }
}
