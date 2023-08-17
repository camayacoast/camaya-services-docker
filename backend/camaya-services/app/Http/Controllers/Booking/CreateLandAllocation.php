<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

use App\Models\Booking\LandAllocation;
use App\Models\Booking\LandAllocationUser;

class CreateLandAllocation extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        if (!$request->allocations) {
            return response()->json(['error' => 'NO_ALLOCATIONS', 'message' => 'Add atleast 1 allocation.'], 400);
        }

        // Loop allocations

        $start = Carbon::parse(date('Y-m-d', strtotime($request->range[0])))->isoFormat('YYYY-MM-DD');
        $end = Carbon::parse(date('Y-m-d', strtotime($request->range[1])))->isoFormat('YYYY-MM-DD');

        $period = CarbonPeriod::create($start, $end);

        // return [$start, $end, $period];

        $land_allocations_to_save = [];

        $to_skip = [];
        $skip_id = 1;


            //
        foreach ($period as $date) {

            $formattedDate = $date->isoFormat('YYYY-MM-DD');

            // Total per entity date to allocate count

            // Check if record already exists
            
            // $remainingAllocations = LandAllocation::whereDate('date', $formattedDate)
            //             ->selectRaw('SUM(allocation) as total_allocation')
            //             ->first();

            $to_allocate = collect($request->allocations)->pluck(strtolower(Carbon::parse($formattedDate)->isoFormat('dddd')))->sum();
            // $remaining_allocation = $roomType->rooms_count - $remainingAllocations['total_allocation'];

            // if ($remaining_allocation <= 0) {
            //     return response()->json(['error' => 'EXCEEDS_REMAINING_ALLOCATION', 'message' => 'Allocation for ['.$formattedDate.'] exceeded the remaining allocation.'], 400);
            // } else if ($remaining_allocation < $to_allocate) {
            //     return response()->json(['error' => 'EXCEEDS_REMAINING_ALLOCATION', 'message' => 'Allocation for ['.$formattedDate.'] will exceed the remaining allocation. Please adjust total "'.Carbon::parse($formattedDate)->isoFormat('dddd').'" allocations to less than or equal ('.$remaining_allocation.').'], 400);
            // }

            foreach ($request->allocations as $allocation) {

                $count = $allocation[strtolower(Carbon::parse($formattedDate)->isoFormat('dddd'))];

                $landAllocation = LandAllocation::whereDate('date', $formattedDate)
                        ->where('entity', $allocation['entity'])
                        ->where('owner_id', $allocation['owner_id'])
                        ->first();

                if ($landAllocation) {
                    // RoomAllocation::where('id', $roomAllocation->id)->update([
                    //     'allocation' => $count ?? 0,
                    //     'updated_at' => Carbon::now(),
                    //     'updated_by' => $request->user()->id,
                    // ]);
                    $to_skip[] = [
                        'id' => $skip_id,
                        'date' => $formattedDate,
                        // 'room_type' => $roomType,
                        'entity' => $allocation['entity'],
                        'land_allocation' => $landAllocation,
                    ];

                    $skip_id++;
                    // return "Already exists. Update manually.";
                } else {
                    if ($count > 0) {
                        $new_land_allocation = LandAllocation::create([
                            'date' => $formattedDate,
                            'entity' => $allocation['entity'],
                            'owner_id' => $allocation['owner_id'],
                            'allowed_roles' => ($allocation['allowed_roles']),
                            'allocation' => $count ?? 0,
                            'status' => 'for_review',
                            'created_by' => $request->user()->id,
                            'created_at' => Carbon::now(),
                        ]);
                        foreach ($allocation['allowed_users'] as $allowed_user) {
                            LandAllocationUser::create([
                                'user_id' => $allowed_user,
                                'land_allocation_id' => $new_land_allocation['id'],
                            ]);
                        }
                    }
                }

            }
            
        }

        // LandAllocation::insert($land_allocations_to_save);

        return response()->json(['skipped' => $to_skip], 200);
        
    }
}
