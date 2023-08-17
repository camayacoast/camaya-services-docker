<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomType;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomRate;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RoomAllocationForBooking extends Controller
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

        return RoomReservation::roomAllocationForBooking([
                'user' =>  $request->user(),
                'arrival' => $request->arrival,
                'departure' => $request->departure,
        ]);

    }
}
