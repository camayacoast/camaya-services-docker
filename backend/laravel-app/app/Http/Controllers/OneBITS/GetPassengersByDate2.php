<?php

namespace App\Http\Controllers\OneBITS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Carbon\Carbon;
use App\Models\Transportation\Passenger;

class GetPassengersByDate2 extends Controller
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
        $date = Carbon::parse($request->date)->setTimezone('Asia/Manila');

        $passengers = Passenger::leftJoin('schedules', 'passengers.trip_number', '=', 'schedules.trip_number')
                                ->leftJoin('trips', 'passengers.id', '=', 'trips.passenger_id')
                                ->select('passengers.*', 'schedules.id as schedule_id', 'schedules.trip_date', 'schedules.start_time', 'schedules.end_time')
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
