<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Carbon\Carbon;
use App\Models\Transportation\Schedule;
use App\Models\Transportation\SeatSegment;

class AvailableTripsByBookingDate extends Controller
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

        $arrival_date = Carbon::parse($request->arrival_date)->format('Y-m-d');
        $departure_date = Carbon::parse($request->departure_date)->format('Y-m-d');

        $isOvernight = ($arrival_date == $departure_date) ? false : true;

        $role_ids = $request->user()->roles->pluck('id');

        $guest_first_trip = [];

        if ($request->trip_data) {
            $guest_first_trip = [
                'origin' => \App\Models\Transportation\Location::where('code', explode("-", $request->trip_data[0])[0])->first(),
                'destination' => \App\Models\Transportation\Location::where('code', explode("-", $request->trip_data[0])[1])->first(),
            ];

            if (isset($request->trip_data[1])) {
                $guest_second_trip = [
                    'origin' => \App\Models\Transportation\Location::where('code', explode("-", $request->trip_data[1])[0])->first(),
                    'destination' => \App\Models\Transportation\Location::where('code', explode("-", $request->trip_data[1])[1])->first(),
                ];
            }
        }

        $second_trip = [];

        $first_trip =  SeatSegment::where(function ($q) use ($role_ids, $request, $isOvernight, $arrival_date, $departure_date, $guest_first_trip) {
                        
                        if ($request->type == 'add_ferry_to_guests') {
                            if ($guest_first_trip['destination']['code'] === 'EST') {
                                $q->whereIn('schedules.trip_date', [$departure_date]);
                            } else {
                                $q->whereIn('schedules.trip_date', [$arrival_date]);
                            }
                        } else {
                            $q->whereIn('schedules.trip_date', [$arrival_date, $departure_date]);
                        }
                        
                        $q->where('schedules.status', 'active');

                        if (!$request->user()->hasRole(['super-admin', 'IT', 'BPO'])) {
                            // $q->whereIn('seat_segment_allows.role_id', $role_ids);
                            // $q->orWhere('seat_segment_allows.user_id', $request->user()->id);   
                            
                            $q->whereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where role_id in (?))', [$role_ids]);
                            $q->orWhereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where user_id = ?)', [$request->user()->id]);
                            
                            // $q->whereHas('allowed_roles', function ($query) use ($role_ids) {
                            //         $query->whereIn('role_id', $role_ids);                  
                            // });

                            // $q->orWhereHas('allowed_users', function ($query) use ($request) {
                            //     $query->where('user_id', $request->user()->id);        
                            // });
                        }
                        
                        // Check if DT or ON
                        if ($isOvernight) {
                            $q->whereRaw('json_contains(booking_type, \'["ON"]\')');
                        } else {
                            $q->whereRaw('json_contains(booking_type, \'["DT"]\')');
                        }
                        
                    })
                    ->join('schedules', 'seat_segments.trip_number', '=', 'schedules.trip_number')
                    ->join('transportations', 'transportations.id', '=', 'schedules.transportation_id')
                    ->join('routes', 'schedules.route_id', '=', 'routes.id')
                    ->join('locations as origin', 'routes.origin_id', '=', 'origin.id')
                    ->join('locations as destination', 'routes.destination_id', '=', 'destination.id')
                    ->join('seat_allocations', 'seat_segments.seat_allocation_id', '=', 'seat_allocations.id')
                    ->whereIn('origin.code', ['EST']) // Set origin as CMY
                    ->whereIn('seat_segments.status', ['published'])
                    // ->whereIn('seat_segments.trip_link', collect($arrival_schedules)->pluck('trip_link')->all())
                    ->where('origin.id', $guest_first_trip['origin']['id'] ?? $request->origin)
                    ->where('destination.id', $guest_first_trip['destination']['id'] ?? $request->destination)
                    ->select(
                        'seat_segments.*',
                        'schedules.trip_date',
                        'schedules.start_time as departure_time',
                        'schedules.end_time as estimated_arrival_time',
                        'schedules.transportation_id',
                        'transportations.name as transportation_name',
                        'transportations.code as transportation_code',
                        'transportations.type as transportation_type',
                        'origin.code as origin_code',
                        'destination.code as destination_code',
                        \DB::raw('(seat_segments.allocated - seat_segments.used) as available'),
                        'seat_allocations.name as allocation_name',
                    )
                    ->get();

        if ($request->trip_type == 'roundtrip') {
            $second_trip =  SeatSegment::where(function ($q) use ($role_ids, $request, $isOvernight, $arrival_date, $departure_date) {
                        
                if ($request->type == 'add_ferry_to_guests') {
                    $q->whereIn('schedules.trip_date', [$departure_date]);
                } else {
                    $q->whereIn('schedules.trip_date', [$arrival_date, $departure_date]);
                }
                $q->whereIn('schedules.trip_date', [$arrival_date, $departure_date]);
                $q->where('schedules.status', 'active');

                if (!$request->user()->hasRole(['super-admin', 'IT','BPO'])) {
                    // $q->whereIn('seat_segment_allows.role_id', $role_ids);
                    // $q->orWhere('seat_segment_allows.user_id', $request->user()->id); 
                    $q->whereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where role_id in (?))', [$role_ids]);
                    $q->orWhereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where user_id = ?)', [$request->user()->id]);
                    
                    // $q->whereHas('allowed_roles', function ($query) use ($role_ids) {
                    //         $query->whereIn('role_id', $role_ids);                  
                    // });

                    // $q->orWhereHas('allowed_users', function ($query) use ($request) {
                    //     $query->where('user_id', $request->user()->id);        
                    // });
                }
                
                // Check if DT or ON
                if ($isOvernight) {
                    $q->whereRaw('json_contains(booking_type, \'["ON"]\')');
                } else {
                    $q->whereRaw('json_contains(booking_type, \'["DT"]\')');
                }
                
            })
            ->join('schedules', 'seat_segments.trip_number', '=', 'schedules.trip_number')
            ->join('transportations', 'transportations.id', '=', 'schedules.transportation_id')
            ->join('routes', 'schedules.route_id', '=', 'routes.id')
            ->join('locations as origin', 'routes.origin_id', '=', 'origin.id')
            ->join('locations as destination', 'routes.destination_id', '=', 'destination.id')
            ->join('seat_allocations', 'seat_segments.seat_allocation_id', '=', 'seat_allocations.id')
            ->whereIn('destination.code', ['EST']) // Set origin as CMY
            ->whereIn('seat_segments.status', ['published'])
            // ->whereIn('seat_segments.trip_link', collect($arrival_schedules)->pluck('trip_link')->all())
            ->where('origin.id', $guest_second_trip['origin']['id'] ?? $request->destination )
            ->where('destination.id', $guest_second_trip['destination']['id'] ?? $request->origin)
            ->select(
                'seat_segments.*',
                'schedules.trip_date',
                'schedules.start_time as departure_time',
                'schedules.end_time as estimated_arrival_time',
                'schedules.transportation_id',
                'transportations.name as transportation_name',
                'transportations.code as transportation_code',
                'transportations.type as transportation_type',
                'origin.code as origin_code',
                'destination.code as destination_code',
                \DB::raw('(seat_segments.allocated - seat_segments.used) as available'),
                'seat_allocations.name as allocation_name',
            )
            ->get();
        }

        return [
            'first_trip' => $first_trip,
            'second_trip' => $second_trip,
        ];
    }
}
