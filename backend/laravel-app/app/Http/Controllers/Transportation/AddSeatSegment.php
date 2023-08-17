<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\SeatAllocation;
use App\Models\Transportation\SeatSegment;

class AddSeatSegment extends Controller
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

        // Check allocation if can allot
        $seat_allocation = SeatAllocation::find($request->seat_allocation_id);

        if (!$seat_allocation) {
            return response()->json(['error' => 'SEAT_ALLOCATION_NOT_FOUND', 'message' => ''], 400);
        }

        $seat_allocation->load('segments');

        $canAllocate = (($seat_allocation->quantity - collect($seat_allocation->segments->toArray())->sum('allocated')) >= $request->allocated);

        if (!$canAllocate) {
            return response()->json(['error' => 'SEAT_SEGMENT_ALLOCATION_FULL', 'message' => 'Failed to save seat segment.'], 400);
        }

        $newSeatSegment = SeatSegment::create([
            'trip_number' => $request->trip_number,
            'seat_allocation_id' => $request->seat_allocation_id,
            'name' => $request->name,
            'active' => 0,
            'allocated' => $request->allocated,
            'rate' => $request->rate,
            'booking_type' => $request->booking_type,
            'status' => $request->status,
            'trip_link' => $request->trip_link,
            'updated_by' => $request->user()->id,
        ]);

        if (!$newSeatSegment) {
            return response()->json(['error' => 'SEAT_SEGMENT_SAVE_FAILED', 'message' => 'Failed to save seat segment.'], 400);
        }
        
        return response()->json($newSeatSegment, 200);
        
    }
}
