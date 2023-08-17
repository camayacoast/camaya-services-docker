<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomRate;

class UpdateRoomRateExcludedDays extends Controller
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

        $roomToUpdate = RoomRate::find($request->id);
        $roomToUpdate->update(['exclude_days' => $request->exclude_days]);           

        return response()->json($roomToUpdate, 200);
    }
}
