<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Schedule;
use App\Models\Transportation\SeatAllocation;

class AddSeatAllocation extends Controller
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

        $schedule = Schedule::find($request->schedule_id);

        if (!$schedule) {
            return response()->json(['error' => 'SCHEDULE_NOT_FOUND', 'message' => ''], 400);
        }

        $schedule = Schedule::where('id', $schedule->id)
        ->with(['transportation' => function ($q) {
            $q->withCount('activeSeats');
        }])
        ->addSelect([
            'allocated_seat' => SeatAllocation::whereColumn('schedule_id', 'schedules.id')->selectRaw('IFNULL(SUM(seat_allocations.quantity), 0) as allocated_seat'),
        ])->first();

        
        $canAllocate = (($schedule->transportation->active_seats_count - $schedule->allocated_seat) >= $request->quantity);

        if (!$canAllocate) {
            return response()->json(['error' => 'SCHEDULE_SEAT_ALLOCATION_FULL', 'message' => 'Failed to save seat allocation.'], 400);
        }

        $newSeatAllocation = SeatAllocation::create([
            'schedule_id' => $request->schedule_id,
            'name' => $request->name,
            'quantity' => $request->quantity,
            'allowed_roles' => $request->allowed_roles,
        ]);


        if (!$newSeatAllocation) {
            return response()->json(['error' => 'SEAT_ALLOCATION_SAVE_FAILED', 'message' => 'Failed to save seat allocation.'], 400);
        }
        
        return response()->json($newSeatAllocation, 200);
    }
}
