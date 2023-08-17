<?php

namespace App\Http\Controllers\Golf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Golf\TeeTimeSchedule;

class TeeTimeAllocationUpdate extends Controller
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

        $tee_time_allocation = TeeTimeSchedule::find($request->id);

        if (!$tee_time_allocation) {
            return response()->json(['error' => 'TEE_TIME_SCHEDULE_NOT_FOUND'], 400);
        }

        TeeTimeSchedule::where('id', $request->id)
            ->update([
                'allocation' => $request->allocation
            ]);

        return $tee_time_allocation;
    }
}
