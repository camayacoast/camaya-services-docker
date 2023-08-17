<?php

namespace App\Http\Controllers\Transportation\Reports;

use App\Http\Controllers\Controller;
use App\Models\Transportation\Schedule;
use Illuminate\Http\Request;

class FerryPassengersManifestoConcierge extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke($start_date, $end_date)
    {
        //        
        if (! $start_date || ! $end_date) {
            return response()->json([
                'status' => true,
                'data' => [],
            ]);
        }

        $data = Schedule::join('routes', 'schedules.route_id', '=', 'routes.id')
                    ->join('locations as origin_location', 'routes.origin_id', '=', 'origin_location.id')
                    ->join('locations as destination_location', 'routes.destination_id', '=', 'destination_location.id')
                    ->join('transportations', 'schedules.transportation_id', '=', 'transportations.id')
                    ->whereBetween('trip_date', [$start_date, $end_date])
                    ->where('schedules.status', '=', 'active')
                    ->with('seatSegments')
                    ->with('seatAllocations.segments.allowed_roles')
                    ->with('seatAllocations.segments.allowed_users')
                    ->with(['transportation' => function ($q) {
                        $q->withCount('activeSeats');
                    }])
                    ->select(
                        'schedules.*',
                        'origin_location.code as origin',
                        'destination_location.code as destination',
                        'transportations.name as transportation',
                    )
                    ->addSelect([
                        'allocated_seat' => \App\Models\Transportation\SeatAllocation::whereColumn('schedule_id', 'schedules.id')->selectRaw('IFNULL(SUM(seat_allocations.quantity), 0) as allocated_seat'),
                        'boarded' => \App\Models\Transportation\Trip::whereColumn('trip_number', 'schedules.trip_number')->where('status','boarded')->selectRaw('IFNULL((COUNT(status)), 0) as boarded'),
                        'checked_in' => \App\Models\Transportation\Trip::whereColumn('trip_number', 'schedules.trip_number')->where('status','checked_in')->selectRaw('IFNULL((COUNT(status)), 0) as checked_in'),
                        'pending' => \App\Models\Transportation\Trip::whereColumn('trip_number', 'schedules.trip_number')->where('status','pending')->selectRaw('IFNULL((COUNT(status)), 0) as pending'),
                        'passengers_total' => \App\Models\Transportation\Passenger::whereColumn('passengers.trip_number', 'schedules.trip_number')->join('trips', 'trips.passenger_id', '=', 'passengers.id')->whereNotIn('trips.status', ['no_show', 'cancelled'])->selectRaw('IFNULL((COUNT(passengers.id)), 0) as passengers_total'),
                    ])
                    ->get();

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }
}
