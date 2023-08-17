<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomType;

class UpdateRoomAllocation extends Controller
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

        // Check if we have actual room available to allocate

        $room_type = RoomType::where('id', $room_allocation->room_type_id)
                        ->withCount('enabledRooms')
                        ->first();

        $allocated = RoomAllocation::whereIn('id', $request->related_ids)
                                    ->where('id', '!=', $room_allocation->id)
                                    ->sum('allocation');

        $available = ($room_type->enabled_rooms_count - $allocated) - $request->new_allocation;

        if ($available < 0) {
            return response()->json(['error' => 'INSUFFICIENT_ALLOCATION', 'message' => "Insufficient allocation."], 400);
        }

        // return [
        //     $available,
        //     $allocated,
        //     // $room_type->enabled_rooms_count
        // ];
        
        // Update allocation
        $room_allocation->update(['allocation' => $request->new_allocation]);

        return response()->json([
            'room_allocation' => $room_allocation->load('room_type'),
            'related_allocation' => RoomAllocation::whereIn('id', $request->related_ids)->get(),
        ], 200);
    }
}
