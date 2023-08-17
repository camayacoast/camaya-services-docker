<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\LandAllocation;

class UpdateLandAllocation extends Controller
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

        $land_allocation->update([
            'allocation'=>$request->value
        ]);

        return $land_allocation;
    }
}
