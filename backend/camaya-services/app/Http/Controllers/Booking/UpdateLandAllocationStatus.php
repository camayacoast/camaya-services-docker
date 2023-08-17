<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\Booking\LandAllocation;

class UpdateLandAllocationStatus extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $land_allocation = LandAllocation::find($request->id);

        if (!$land_allocation) {
            return response()->json(['error' => 'LAND_ALLOCATION_NOT_FOUND', 'message' => 'Land allocation does not exist.'], 400);
        }

        $land_allocation->status = $request->new_status;
        $land_allocation->updated_at = Carbon::now();
        $land_allocation->updated_by = $request->user()->id;

        if (!$land_allocation->save()) {
            return response()->json(['error' => 'LAND_ALLOCATION_CHANGE_STATUS_FAILED', 'message' => 'Failed to update land allocation status.'], 400);
        }

        return response()->json($land_allocation, 200);
    }
}
