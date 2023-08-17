<?php

namespace App\Http\Controllers\OneBITS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Carbon\Carbon;
use App\Models\Transportation\Passenger;

class GetPassengersByDate extends Controller
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

        $date = Carbon::parse($request->date)->setTimezone('Asia/Manila')->format('Y-m-d');

        $passengers = Passenger::leftJoin('schedules', 'passengers.trip_number', '=', 'schedules.trip_number')
                                ->leftJoin('trips', 'passengers.id', '=', 'trips.passenger_id')
                                ->leftJoin('routes', 'schedules.route_id', '=', 'routes.id')
                                ->leftJoin('locations as origin', 'routes.origin_id', '=', 'origin.id')
                                ->leftJoin('locations as destination', 'routes.destination_id', '=', 'destination.id')
                                ->select(
                                    'passengers.*',
                                    'schedules.id as schedule_id',
                                    'schedules.trip_date',
                                    'schedules.start_time',
                                    'schedules.end_time',
                                    
                                    'origin.code as origin_code',
                                    'destination.code as destination_code',
                                )
                                ->where( function ($q) use ($request, $date) {
                                    if ($request->schedule) {
                                        $q->where('schedules.id', $request->schedule);
                                    } else {
                                        $q->where('schedules.trip_date', $date);
                                    }

                                    $q->where('trips.ticket_reference_number', '!=', '1');
                                })
                                ->with(['trip' => function ($q) {
                                    $q->where('ticket_reference_number', '!=', '1');
                                }])
                                ->with('ticket')
                                ->get();

        return $passengers;
    }
}
