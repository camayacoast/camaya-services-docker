<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\SeatSegment;
use App\Models\Transportation\SeatAllocation;

class UpdateSeatSegmentAllocated extends Controller
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

        $seat_segment = SeatSegment::find($request->seat_segment_id);
        
        if (!$seat_segment) {
            return response()->json(['error' => 'SEAT_SEGMENT_NOT_FOUND', 'message' => 'Seat segment not found.'], 400);
        }

        $seat_allocation = SeatAllocation::where('id', $seat_segment->seat_allocation_id)->first();

        // Min
        if ($request->allocated < $seat_segment->used) {
            return response()->json(['error' => 'QUANTITY_LOWER_THAN_OPEN_SEAT_ALLOCATED', 'message' => ''], 400);
        }

        $total_seat_segments_allocated = collect($seat_allocation->segments->toArray())->sum('allocated');

        $open = ($seat_allocation->quantity - $total_seat_segments_allocated) + $seat_segment->allocated;

        // Max
        if ($request->allocated > $open) {
            return response()->json(['error' => 'QUANTITY_HIGHER_THAN_OPEN_SEAT_ALLOCATED', 'message' => ''], 400);
        }

        $seat_segment->update([
            'allocated' => $request->allocated
        ]);

        return response()->json($seat_segment, 200);
    }
}
