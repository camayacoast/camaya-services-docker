<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\SeatSegment;

use Carbon\Carbon;

class GetAvailableCamayaTransportationSchedules extends Controller
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
        /**
         * Set the check availability of camaya ferry or camaya transpo
         */
        $isCamayaTransportationAvailable = false;
        $arrival_schedules_array = [];
        $departure_schedules_array = [];
            
        /**
         * Start
         */

        $role_ids = $request->user()->roles->pluck('id');

        $arrival_date = Carbon::parse(date('Y-m-d', strtotime($request->date_of_visit[0])))->setTimezone('Asia/Manila')->format('Y-m-d');
        $departure_date = Carbon::parse(date('Y-m-d', strtotime($request->date_of_visit[1])))->setTimezone('Asia/Manila')->format('Y-m-d');

        $isOvernight = false;
        if ($arrival_date != $departure_date) {
            $isOvernight = true;
        }

        // $request->start_date $request->end_date
        $arrival_schedules = \App\Models\Transportation\SeatSegment::where(function ($q) use ($role_ids, $request, $isOvernight, $arrival_date) {
                                    
                                    if (!$request->user()->hasRole(['super-admin', 'IT', 'BPO'])) {
                                        // $q->whereIn('seat_segment_allows.role_id', $role_ids);
                                        // $q->orWhere('seat_segment_allows.user_id', $request->user()->id); 
                                        
                                        $q->whereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where user_id = ?)', [$request->user()->id]);
                                        $q->orWhereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where role_id in (?))', [$role_ids]);
                                        
                                        // $q->whereHas('allowed_roles', function ($query) use ($role_ids, $request) {
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
                                // // // ->with('allowed_roles')
                                // // // ->with('allowed_users')
                                ->join('schedules', 'seat_segments.trip_number', '=', 'schedules.trip_number')
                                ->join('transportations', 'transportations.id', '=', 'schedules.transportation_id')
                                ->join('routes', 'schedules.route_id', '=', 'routes.id')
                                ->join('locations as origin', 'routes.origin_id', '=', 'origin.id')
                                ->join('locations as destination', 'routes.destination_id', '=', 'destination.id')
                                ->whereIn('origin.code', ['EST']) // Set origin as EST
                                ->whereIn('seat_segments.status', ['published'])
                                ->where('schedules.trip_date', $arrival_date)
                                ->where('schedules.status', 'active')
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
                                )
                                ->get();

        foreach ($arrival_schedules as $arrival_schedule) {
            // $arrival_schedule['active']
            // $arrival_schedule['allocated']
            // $arrival_schedule['used']

            $arrival_schedules_array[] = [
                'seat_segment_id' => $arrival_schedule['id'],
                'name' => $arrival_schedule['name'],
                'trip_date' => $arrival_schedule['trip_date'],
                'departure_time' => $arrival_schedule['departure_time'],
                'estimated_arrival_time' => $arrival_schedule['estimated_arrival_time'],
                'trip_link' => $arrival_schedule['trip_link'],
                'rate' => $arrival_schedule['rate'],
                'transportation' => [
                    'name' => $arrival_schedule['transportation_name'],
                    'code' => $arrival_schedule['transportation_code'],
                    'type' => $arrival_schedule['transportation_type'],
                ],
                'origin' => $arrival_schedule['origin_code'],
                'destination' => $arrival_schedule['destination_code'],
                'available' => $arrival_schedule['allocated'] - ($arrival_schedule['used'] + $arrival_schedule['active']),
            ];
        }
        

        $departure_schedules = [];
        // if (count($arrival_schedules)) {
            $departure_schedules = \App\Models\Transportation\SeatSegment::where(function ($q) use ($role_ids, $request, $isOvernight) {

                                    if (!$request->user()->hasRole(['super-admin', 'IT', 'BPO'])) {
                                        // $q->whereIn('seat_segment_allows.role_id', $role_ids);
                                        // $q->orWhere('seat_segment_allows.user_id', $request->user()->id); 
                                        
                                        $q->whereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where user_id = ?)', [$request->user()->id]);
                                        $q->orWhereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where role_id in (?))', [$role_ids]);
                                        
                                        
                                        // $q->whereHas('allowed_roles', function ($query) use ($role_ids, $request) {
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
                                ->whereIn('destination.code', ['EST']) // Set origin as CMY
                                ->whereIn('seat_segments.status', ['published'])
                                // ->whereIn('seat_segments.trip_link', collect($arrival_schedules)->pluck('trip_link')->all())
                                ->where('schedules.trip_date', $departure_date)
                                ->where('schedules.status', 'active')
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
                                )
                                ->get();

            foreach ($departure_schedules as $departure_schedule) {
                // $arrival_schedule['active']
                // $arrival_schedule['allocated']
                // $arrival_schedule['used']

                $departure_schedules_array[] = [
                    'seat_segment_id' => $departure_schedule['id'],
                    'name' => $departure_schedule['name'],
                    'trip_date' => $departure_schedule['trip_date'],
                    'departure_time' => $departure_schedule['departure_time'],
                    'estimated_arrival_time' => $departure_schedule['estimated_arrival_time'],
                    'trip_link' => $departure_schedule['trip_link'],
                    'rate' => $departure_schedule['rate'],
                    'transportation' => [
                        'name' => $departure_schedule['transportation_name'],
                        'code' => $departure_schedule['transportation_code'],
                        'type' => $departure_schedule['transportation_type'],
                    ],
                    'origin' => $departure_schedule['origin_code'],
                    'destination' => $departure_schedule['destination_code'],
                    'available' => $departure_schedule['allocated'] - ($departure_schedule['used'] + $departure_schedule['active']),
                ];
            }
        // }

        $isCamayaTransportationAvailable = (count($arrival_schedules) && count($departure_schedules ?? [])) ? true : false;
            
        /**
         * End
         */

        return [
            'arrival_schedules' => $arrival_schedules_array,
            'departure_schedules' => $departure_schedules_array,
            'camaya_transporation_available' => $isCamayaTransportationAvailable,
        ];

    }
}
