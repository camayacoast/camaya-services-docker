<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\LandAllocation;
use App\Models\Booking\LandAllocationUser;
use App\User;

class UpdateLandAllocationAllowedUsers extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // return $request->all();

        $land_allocation = LandAllocation::find($request->id);

        if (!$land_allocation) {
            return response()->json(['error' => 'ERROR', 'message' => "Error"], 400);
        }

        // Remove all allowed roles
        LandAllocationUser::where('land_allocation_id', $request->id)->whereNotNull('user_id')->delete();


        $user_ids = User::whereIn('id', $request->new_users)->select('id as user_id')->get()->toArray();

        $land_allocation->allowed_users()->createMany($user_ids);

        return $land_allocation;
    }
}
