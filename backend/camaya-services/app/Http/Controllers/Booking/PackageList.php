<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Package;

use App\Models\Main\Role;

use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomType;

use Carbon\CarbonPeriod;
use Carbon\Carbon;

class PackageList extends Controller
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

        $role_ids = $request->user()->roles->pluck('id');

        $override_roles = ['super-admin', 'IT'];
        
        $packages = Package::with([
                    'images', 
                    'allowedRoles:id,package_id,role_id',
                    'allowedSources:id,package_id,source',
                    'packageInclusions:id,package_id,related_id,quantity,type',
                    'packageRoomTypeInclusions:id,package_id,related_id,quantity,type,entity'
                ])
                // ->leftJoin('package_allow_roles', 'packages.id', '=', 'package_allow_roles.package_id')
                ->where(function ($q) use ($role_ids, $request, $override_roles) {
                    // if (!$request->user()->hasRole(['super-admin', 'IT', 'BPO'])) {
                    if (!$request->user()->hasRole($override_roles)) {
                        // $q->whereIn('package_allow_roles.role_id', $role_ids);
                        $q->whereRaw('packages.id in (select package_id from package_allow_roles where role_id in (?))', [$role_ids]);
                        // $q->whereHas('allowedRoles', function ($query) use ($role_ids, $request) {
                        //         $query->whereIn('role_id', $role_ids);                        
                        // });
                    }
                    
                    if ($request->inventory == 1) {
                        $q->whereIn('status', ['published', 'unpublished', 'expired', 'ended']);
                    } else {
                        $q->whereIn('status', ['published', 'unpublished']);
                    }

                })
                ->orderBy('name')
                ->select('packages.*')
                ->groupBy('packages.id')
                ->get();


        if (isset($request->start_date)) {

            $arrival = $request->start_date." 12:00:00";
            $departure = $request->end_date." 11:00:00";

            $period = CarbonPeriod::create($request->start_date, Carbon::parse($request->end_date)->subDays(1));

            $packages_with_availability = [];

            foreach ($packages as $package) {

                    $room_type = collect($package['packageRoomTypeInclusions'])->firstWhere('type', 'room_type');

                    // Check if all dates have available room types
                    // 
                    if ($room_type) {
                        $package['room_type'] = RoomType::where('id', $room_type['related_id'])->first();
                    }

                    $room_allocations = RoomAllocation::where( function ($q) use ($request) {
                                    $q->whereDate('date', '>=', $request->start_date);
                                    $q->whereDate('date', '<', $request->end_date);
                                })
                                ->where('status', 'approved')
                                // ->where('room_type_id', $room_type['related_id'] ?? null)
                                ->where( function ($query) use ($room_type) {
                                    if ($room_type) {
                                        $query->where('room_type_id', $room_type['related_id']);
                                    }
                                })
                                // ->whereRaw('json_contains(allowed_roles, \'["'.$room_type['entity'].'"]\')') // Accepts customer only at website booking
                                ->get();

                    $available = [];

                    $available_package = 0;

                    foreach ($period as $date) {

                        $formattedDate = $date->isoFormat('YYYY-MM-DD');

                        $rt = collect($room_allocations)
                                        ->first(function ($rm, $key) use ($formattedDate, $room_type) {
                                            if ($rm && $room_type) {
                                                return (
                                                    (date('Y-m-d', strtotime($rm['date'])) == $formattedDate) &&
                                                    ($rm['entity'] == $room_type['entity'])
                                                );
                                            } else {
                                                return false;
                                            }
                                        });
                        if ($rt) {
                            $available[] = $rt['allocation'] - $rt['used'];
                        } else {
                            $available[] = 0;
                        }

                    }
                    

                    $available_package = array_search(null, $available) === true ? 0 : collect($available)->min();

                    // Set availability here if package does not have a room
                    if (count($package['packageRoomTypeInclusions']) == 0) {
                        $available_package = 20;
                    }

                    /**
                     * Set the check availability of camaya ferry or camaya transpo
                     */
                    $isCamayaTransportationAvailable = false;
                    $arrival_schedules_array = [];
                    $departure_schedules_array = [];

                    if ($package['mode_of_transportation'] == 'camaya_transportation') {
                        
                        /**
                         * Start
                         */

                        // Get schedules of arrival and departure

                        // $request->start_date $request->end_date
                        $arrival_schedules = \App\Models\Transportation\SeatSegment::where(function ($q) use ($role_ids, $request, $package, $override_roles) {
                                                    if (!$request->user()->hasRole($override_roles)) {
                                                        $q->whereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where user_id = ?)', [$request->user()->id]);
                                                        $q->orWhereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where role_id in (?))', [$role_ids]);
                                                        // $q->whereIn('seat_segment_allows.role_id', $role_ids);
                                                        // $q->orWhere('seat_segment_allows.user_id', $request->user()->id);   
                                                        // $q->whereHas('allowed_roles', function ($query) use ($role_ids, $request) {
                                                        //         $query->whereIn('role_id', $role_ids);                  
                                                        // });

                                                        // $q->orWhereHas('allowed_users', function ($query) use ($request) {
                                                        //     $query->where('user_id', $request->user()->id);        
                                                        // });
                                                    }
                                                    
                                                    if ($package['availability'] == "for_dtt") {
                                                        $q->whereRaw('json_contains(booking_type, \'["DT"]\')');
                                                    } else if ($package['availability'] == "for_overnight") {
                                                        $q->whereRaw('json_contains(booking_type, \'["ON"]\')');
                                                    } else if ($package['availability'] == "for_dtt_overnight") {
                                                        $q->whereRaw('json_contains(booking_type, \'["DT","ON"]\')');
                                                    }

                                                })
                                                // ->with('allowed_roles')
                                                // ->with('allowed_users')
                                                ->join('schedules', 'seat_segments.trip_number', '=', 'schedules.trip_number')
                                                ->join('transportations', 'transportations.id', '=', 'schedules.transportation_id')
                                                ->join('routes', 'schedules.route_id', '=', 'routes.id')
                                                ->join('locations as origin', 'routes.origin_id', '=', 'origin.id')
                                                ->join('locations as destination', 'routes.destination_id', '=', 'destination.id')
                                                ->whereIn('origin.code', ['EST']) // Set origin as EST
                                                ->where('schedules.trip_date', $request->start_date)
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
                                                ->orderBy('seat_segments.id')
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
                        if (count($arrival_schedules)) {
                            $departure_schedules = \App\Models\Transportation\SeatSegment::where(function ($q) use ($role_ids, $request, $package, $override_roles) {
                                                    if (!$request->user()->hasRole($override_roles)) {
                                                        $q->whereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where user_id = ?)', [$request->user()->id]);
                                                        $q->orWhereRaw('seat_segments.id in (select seat_segment_id from seat_segment_allows where role_id in (?))', [$role_ids]);
                                                        // $q->whereHas('allowed_roles', function ($query) use ($role_ids, $request) {
                                                        //         $query->whereIn('role_id', $role_ids);                  
                                                        // });

                                                        // $q->orWhereHas('allowed_users', function ($query) use ($request) {
                                                        //     $query->where('user_id', $request->user()->id);        
                                                        // });
                                                    }
                                                    
                                                    if ($package['availability'] == "for_dtt") {
                                                        $q->whereRaw('json_contains(booking_type, \'["DT"]\')');
                                                    } else if ($package['availability'] == "for_overnight") {
                                                        $q->whereRaw('json_contains(booking_type, \'["ON"]\')');
                                                    } else if ($package['availability'] == "for_dtt_overnight") {
                                                        $q->whereRaw('json_contains(booking_type, \'["DT","ON"]\')');
                                                    }

                                                })
                                                ->join('schedules', 'seat_segments.trip_number', '=', 'schedules.trip_number')
                                                ->join('transportations', 'transportations.id', '=', 'schedules.transportation_id')
                                                ->join('routes', 'schedules.route_id', '=', 'routes.id')
                                                ->join('locations as origin', 'routes.origin_id', '=', 'origin.id')
                                                ->join('locations as destination', 'routes.destination_id', '=', 'destination.id')
                                                ->whereIn('destination.code', ['EST']) // Set origin as CMY
                                                ->whereIn('seat_segments.trip_link', collect($arrival_schedules)->pluck('trip_link')->all())
                                                ->where('schedules.trip_date', $request->end_date)
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
                                                ->orderBy('seat_segments.id')
                                                ->get();

                            foreach ($departure_schedules as $departure_schedule) {
                                // $arrival_schedule['active']
                                // $arrival_schedule['allocated']
                                // $arrival_schedule['used']
    
                                $departure_schedules_array[] = [
                                    'seat_segment_id' => $departure_schedule['id'],
                                    'name' => $arrival_schedule['name'],
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
                        }

                        $isCamayaTransportationAvailable = (count($arrival_schedules) || count($departure_schedules)) ? true : false;
                        
                        /**
                         * End
                         */

                        // return [
                        //     $arrival_schedules,
                        //     $departure_schedules,
                        //     $isCamayaTransportationAvailable,
                        // ];

                    }

                    $packages_with_availability[] = [
                        'name' => $package['name'],
                        'allowed_roles' => $package['allowed_roles'],
                        'allowed_days' => $package['allowed_days'],
                        'availability' => $package['availability'],
                        'category' => $package['category'],
                        'booking_end_date' => $package['booking_end_date'],
                        'booking_start_date' => $package['booking_start_date'],
                        'code' => $package['code'],
                        'description' => $package['description'],
                        'exclude_days' => $package['exclude_days'],
                        // 'holidays' => $package['holidays'],
                        'id' => $package['id'],
                        'max_adult' => $package['max_adult'],
                        'max_infant' => $package['max_infant'],
                        'max_kid' => $package['max_kid'],
                        'min_adult' => $package['min_adult'],
                        'min_infant' => $package['min_infant'],
                        'min_kid' => $package['min_kid'],
                        'mode_of_transportation' => $package['mode_of_transportation'],
                        'quantity_per_day' => $package['quantity_per_day'],
                        'regular_price' => $package['regular_price'],
                        'weekday_rate' => $package['weekday_rate'],
                        'weekend_rate' => $package['weekend_rate'],
                        // 'promo_rate' => $package['promo_rate'],
                        'selling_end_date' => $package['selling_end_date'],
                        'selling_price' => $package['selling_price'],
                        'selling_start_date' => $package['selling_start_date'],
                        'status' => $package['status'],
                        'stocks' => $package['stocks'],
                        'type' => $package['type'],
                        'walkin_price' => $package['walkin_price'],
                        'package_inclusions' => $package['packageInclusions'],
                        'package_room_type_inclusions' => $package['packageRoomTypeInclusions'], 
                        'package_room_type' => $package['room_type'],
                        'images' => $package['images'],
                        'available' => $available_package,
                        'camaya_transportation_available' => $isCamayaTransportationAvailable,
                        'arrival_schedules' => $arrival_schedules_array,
                        'departure_schedules' => $departure_schedules_array,
                    ];

            }
        }

        return isset($request->start_date) ? $packages_with_availability : $packages;
    }
}
