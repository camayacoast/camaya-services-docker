<?php

namespace App\Http\Controllers\OneBITS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\OneBITS\GetAvailableTripsRequest;
use App\Models\Transportation\SeatSegment;
use App\Models\Transportation\Route;
use App\Models\Transportation\Location;
use Carbon\Carbon;
use DB;
use App\Models\Main\Role;

class GetAvailableTrips extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(GetAvailableTripsRequest $request)
    {
        //
        // return $request->all();

        /**
         * Total passengers
         */
        $total_passengers = $request->total_passengers['adult'] + $request->total_passengers['kid'];

        /**
         * Get selected route
         */
        $location = explode("_", $request->selected_route);

        $origin = Location::where('code', $location[0])->first();
        $destination = Location::where('code', $location[1])->first();

        if (!$origin || !$destination) {
            return response()->json(['error' => 'Route does not exist.'], 402);
        }

        $route = Route::where('origin_id', $origin->id)
                    ->where('destination_id', $destination->id)
                    ->first();

        // Get roundtrip
        $roundtrip_route = Route::where('origin_id', $destination->id)
                    ->where('destination_id', $origin->id)
                    ->first();

        /**
         * Load trips here
         */
        $date = Carbon::parse($request->selected_date)->setTimezone('Asia/Manila')->format('Y-m-d');

        $passenger_role = Role::whereIn('name', ['1BITS Passenger'])->first();

        $seat_segments = SeatSegment::leftJoin('schedules', 'schedules.trip_number', '=', 'seat_segments.trip_number')
                                ->leftJoin('seat_allocations', 'seat_segments.seat_allocation_id', '=', 'seat_allocations.id')
                                ->leftJoin('routes', 'schedules.route_id', '=', 'routes.id')
                                ->leftJoin('locations as origin', 'routes.origin_id', '=', 'origin.id')
                                ->leftJoin('locations as destination', 'routes.destination_id', '=', 'destination.id')
                                ->where('schedules.trip_date', $date)
                                ->where('seat_allocations.name', '1BITS')
                                ->where('seat_segments.status', 'published')
                                ->where('schedules.status', 'active')
                                ->whereIn('schedules.route_id', [$route->id, $roundtrip_route->id])
                                ->where(function($q) use ($request, $passenger_role) {
                                    
                                    if ($request->admin !== 1) {
                                        $q->whereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where role_id = ?)', [$passenger_role->id]);
                                    }

                                })
                                ->select(
                                    'schedules.start_time',
                                    'schedules.end_time',
                                    'schedules.trip_date',
                                    'seat_segments.id',
                                    'seat_segments.seat_allocation_id',
                                    'seat_segments.allocated',
                                    'seat_segments.used',
                                    'seat_segments.name',
                                    'seat_segments.rate',
                                    'seat_segments.trip_number',
                                    'origin.code as origin_code',
                                    'destination.code as destination_code',
                                    'origin.name as origin_name',
                                    'destination.name as destination_name',
                                    DB::raw('((seat_segments.allocated - seat_segments.used)) as available'),
                                )
                                ->get();
        
        $seat_segments = $seat_segments->each( function ($q) use ($total_passengers, $origin, $destination) {
            $q['remaining_seats'] = (($q['available']) - $total_passengers);

            $q['origin'] = $origin['origin_name'];
            $q['origin_code'] = $q['origin_code'];

            $q['destination'] = $destination['destination_name'];
            $q['destination_code'] = $q['destination_code'];
            return $q;
        });

        return $seat_segments;
    }
}
