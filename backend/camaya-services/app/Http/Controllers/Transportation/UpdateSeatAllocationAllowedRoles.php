<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\SeatAllocation;

class UpdateSeatAllocationAllowedRoles extends Controller
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

        $seat_allocation = SeatAllocation::find($request->id);

        if (!$seat_allocation) {
            return response()->json(['error' => 'ERROR', 'message' => "Error"], 400);
        }

        $seat_allocation->update(['allowed_roles' => $request->new_roles]);

        return $seat_allocation;
    }
}
