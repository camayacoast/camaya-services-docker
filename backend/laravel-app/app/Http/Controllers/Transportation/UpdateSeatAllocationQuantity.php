<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Schedule;
use App\Models\Transportation\SeatAllocation;

class UpdateSeatAllocationQuantity extends Controller
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

        $seat_allocation = SeatAllocation::find($request->seat_allocation_id);
        
        if (!$seat_allocation) {
            return response()->json(['error' => 'SEAT_ALLOCATION_NOT_FOUND', 'message' => 'Seat allocation not found.'], 400);
        }

        $seat_allocation->load('segments');

        $schedule = Schedule::where('id', $seat_allocation->schedule_id)
                        ->with(['transportation' => function ($q) {
                            $q->withCount('activeSeats');
                        }])
                        ->with('seatAllocations')
                        ->first();

        // Allocated (should be the minumum); Can not go lower than the already alloted slot
        $total_allocated_segments = collect($seat_allocation->segments->toArray())->sum('allocated');

        // Allocated seat
        // $seat_allocation->quantity;
        $total_seat_allocations = collect($schedule->seatAllocations->toArray())->sum('quantity');

        $open = ($schedule->transportation['active_seats_count'] - $total_seat_allocations) + $seat_allocation->quantity;

        // return [
        //     $open,
        //     $total_seat_allocations,
        //     $total_allocated_segments,
        //     $seat_allocation,
        //     $request->quantity
        // ];

        if ($request->quantity < $total_allocated_segments) {
            return response()->json(['error' => 'QUANTITY_LOWER_THAN_OPEN_SEAT_ALLOCATIONS', 'message' => ''], 400);
        }

        if ($request->quantity > $open) {
            return response()->json(['error' => 'QUANTITY_HIGHER_THAN_OPEN_SEAT_ALLOCATIONS', 'message' => ''], 400);
        }

        $seat_allocation->update([
            'quantity' => $request->quantity
        ]);

        return response()->json($seat_allocation, 200);

        
    }
}
