<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomRate;

class UpdateRoomRateAllowedDays extends Controller
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
        $roomToUpdate->update(['days_interval' => $request->days_interval]);           

        return response()->json($roomToUpdate, 200);
    }
}
