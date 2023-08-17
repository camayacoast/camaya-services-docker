<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CreateRoomAllocation extends Controller
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

        if (!$request->allocations) {
            return response()->json(['error' => 'NO_ALLOCATIONS', 'message' => 'Add atleast 1 allocation.'], 400);
        }

        // Loop allocations

        $start = Carbon::parse(date('Y-m-d', strtotime($request->range[0])))->isoFormat('YYYY-MM-DD');
        $end = Carbon::parse(date('Y-m-d', strtotime($request->range[1])))->isoFormat('YYYY-MM-DD');

        $period = CarbonPeriod::create($start, $end);

        // return [$start, $end, $period];

        $room_allocations_to_save = [];

        $to_skip = [];
        $skip_id = 1;


            //
        foreach ($period as $date) {

            $formattedDate = $date->isoFormat('YYYY-MM-DD');

            // Total per entity date to allocate count

            // Check if record already exists

            $roomType = RoomType::where('id', $request->room_type_id)
                                ->select('id', 'name')
                                ->withCount('rooms')
                                ->first();
            
            $remainingAllocations = RoomAllocation::whereDate('date', $formattedDate)
                        ->where('room_type_id', $request->room_type_id)
                        ->selectRaw('SUM(allocation) as total_allocation')
                        ->first();

            $to_allocate = collect($request->allocations)->pluck(strtolower(Carbon::parse($formattedDate)->isoFormat('dddd')))->sum();
            $remaining_allocation = $roomType->rooms_count - $remainingAllocations['total_allocation'];

            if ($remaining_allocation <= 0) {
                return response()->json(['error' => 'EXCEEDS_REMAINING_ALLOCATION', 'message' => 'Allocation for ['.$formattedDate.'] exceeded the remaining allocation.'], 400);
            } else if ($remaining_allocation < $to_allocate) {
                return response()->json(['error' => 'EXCEEDS_REMAINING_ALLOCATION', 'message' => 'Allocation for ['.$formattedDate.'] will exceed the remaining allocation. Please adjust total "'.Carbon::parse($formattedDate)->isoFormat('dddd').'" allocations to less than or equal ('.$remaining_allocation.').'], 400);
            }

            foreach ($request->allocations as $allocation) {

                $count = $allocation[strtolower(Carbon::parse($formattedDate)->isoFormat('dddd'))];

                $roomAllocation = RoomAllocation::whereDate('date', $formattedDate)
                        ->where('room_type_id', $request->room_type_id)
                        ->where('entity', $allocation['entity'])
                        ->first();

                if ($roomAllocation) {
                    // RoomAllocation::where('id', $roomAllocation->id)->update([
                    //     'allocation' => $count ?? 0,
                    //     'updated_at' => Carbon::now(),
                    //     'updated_by' => $request->user()->id,
                    // ]);
                    $to_skip[] = [
                        'id' => $skip_id,
                        'date' => $formattedDate,
                        'room_type' => $roomType,
                        'entity' => $allocation['entity'],
                        'room_allocation' => $roomAllocation,
                    ];

                    $skip_id++;
                    // return "Already exists. Update manually.";
                } else {
                    if ($count > 0) {
                        $room_allocations_to_save[] = [
                            'room_type_id' => $request->room_type_id,
                            'date' => $formattedDate,
                            'entity' => $allocation['entity'],
                            'allowed_roles' => json_encode($allocation['allowed_roles']),
                            'allocation' => $count ?? 0,
                            'status' => 'for_review',
                            'created_by' => $request->user()->id,
                            'created_at' => Carbon::now(),
                        ];
                    }
                }

            }
            
        }

        RoomAllocation::insert($room_allocations_to_save);


        return response()->json(['skipped' => $to_skip], 200);
        
    }
}
