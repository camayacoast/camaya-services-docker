<?php

namespace App\Http\Controllers\OneBITS\Admin\Reports;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OneBITS\SalesReportManifest;

use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Schedule;
use App\Models\Transportation\Passenger;

use DB;

class ArrivalForeCast extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke($start_date, $end_date)
    {

        $start_date = Carbon::parse($start_date)->setTimezone('Asia/Manila')->format('Y-m-d');
        $end_date = Carbon::parse($end_date)->setTimezone('Asia/Manila')->format('Y-m-d');

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
                        ->whereBetween('schedules.trip_date', [$start_date, $end_date])
                        ->where('trips.ticket_reference_number', '!=', '1')
                        ->with(['trip' => function ($q) {
                            $q->where('ticket_reference_number', '!=', '1');
                        }])
                        ->with('ticket')
                        ->get();

        $statuses = ['pending', 'checked_in', 'boarded', 'no_show', 'cancelled'];

        $statusCounts = $passengers->groupBy('trip.status')
            ->map(function ($passengers) {
                return $passengers->count();
            })
            ->union(array_fill_keys($statuses, 0))
            ->toArray();

        return response()->json([
            'passengers' => $passengers,
            'statusCounts' => $statusCounts,
            'startdate' => $start_date,
            'total' => $passengers->count()
        ]);
        
    }
}
