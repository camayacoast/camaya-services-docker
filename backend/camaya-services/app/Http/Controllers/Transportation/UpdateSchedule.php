<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Schedule;
use App\Models\Transportation\Route as TransportationRoute;
use App\Models\Transportation\Location;

use Carbon\Carbon;

class UpdateSchedule extends Controller
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

        $origin = Location::where('code', $request->origin)->first();
        $destination = Location::where('code', $request->destination)->first();

        $newRoute = TransportationRoute::where('origin_id', $origin['id'])->where('destination_id', $destination['id'])->first();

        $scheduleToEdit = Schedule::where('trip_number', $request->trip_number)->first();

        $scheduleToEdit->update([
            'start_time' => Carbon::parse($request->start_time)->setTimezone('Asia/Manila')->format('H:i:s'),
            'end_time' => Carbon::parse($request->end_time)->setTimezone('Asia/Manila')->format('H:i:s'),
            'route_id' => $newRoute['id']
        ]);

        // return $scheduleToEdit->refresh();

        return $scheduleToEdit->refresh();
    }
}
