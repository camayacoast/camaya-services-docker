<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomType;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomRate;
use App\Models\Hotel\Room;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AgentPortalRoomAllocationForBooking extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function  __invoke(Request $request) {

        $arrival = $request['arrival']." 12:00:00";
        $departure = $request['departure']." 11:00:00";

        $period = CarbonPeriod::create($request['arrival'], Carbon::parse($request['departure'])->subDays(1));

        $dates = collect($period)->map(function ($item, $key) {
            return $item->isoFormat('YYYY-MM-DD');
        })->all();

        $room_allocations = RoomAllocation::where( function ($q) use ($request, $dates) {
                    $q->whereDate('date', '>=', $request['arrival']);
                    $q->whereDate('date', '<', $request['departure']);
                })
                ->where('status', 'approved')
                ->select('allowed_roles','allocation', 'date', 'entity', 'id', 'room_type_id', 'status', 'used')
                ->selectRaw('(allocation - used) as available')
                ->get();

        $room_types_with_allocations = [];

        if ($request->user()->user_type == 'agent' && isset($request->user()->other['allowed_entity'])) {

            // $agent = \App\User::where('id', $request->user()->id)->first();
            $entities = [$request->user()->other['allowed_entity']]; // Update this to a pivot table or something manageable
        } else {
            $entities = [];
        }

        foreach ($entities as $entity) {
            foreach (collect($room_allocations)->groupBy('room_type_id')->toArray() as $room_type_id => $items) {

                $available_rooms_per_room_type = Room::whereNotIn('id', function ($query) use ($room_type_id, $arrival, $departure) {
                                                                $query->from('room_reservations')
                                                                    ->whereIn('room_type_id', [$room_type_id]
                                                                )
                                                                ->where(function ($query) use ($arrival, $departure) {
                                                                    $query->where(function ($query) use ($arrival, $departure) {
                                                                        $query->where('start_datetime', '<=', $arrival)
                                                                            ->where('end_datetime', '>=', $arrival);
                                                                    })->orWhere(function ($query) use ($arrival, $departure) {
                                                                        $query->where('start_datetime', '<=', $departure)
                                                                            ->where('end_datetime', '>=', $departure);
                                                                    })->orWhere(function ($query) use ($arrival, $departure) {
                                                                        $query->where('start_datetime', '>=', $arrival)
                                                                            ->where('end_datetime', '<', $departure);
                                                                    });
                                                                })
                                                                ->whereNotNull('room_id')
                                                                ->whereIn('status', ['confirmed', 'pending', 'checked_in', 'checked_out', 'blackout'])
                                                                ->select('room_id')
                                                                ->pluck('room_id');
                                                    })
                                                    ->whereIn('room_type_id',
                                                        [$room_type_id]
                                                    )
                                                    ->where('enabled', 1)
                                                    ->select('id as room_id', 'room_type_id')
                                                    ->get();

                /**
                 * Get booked/blackout rooms per room type
                 */

                $room_allocation_dates = collect($items)->where('entity', $entity)
                                            ->map(function ($item, $key) use ($entity) {
                                                return date('Y-m-d', strtotime($item['date']));
                                            })->all();

                $diff = collect($dates)->diff($room_allocation_dates);

                $room_type = RoomType::where('id', $room_type_id)->withCount('enabledRooms')->with('images')->with('property')->first();

                $room_rate_total = 0;
                foreach ($dates as $date) {

                    /**
                     * Room rate
                     */
                    $room_rate = RoomRate::where('room_type_id', $room_type_id)
                                            ->whereDate('start_datetime', '<=', $date)
                                            ->whereDate('end_datetime', '>=', $date)
                                            ->whereRaw('json_contains(days_interval, \'["'. strtolower(Carbon::parse($date)->isoFormat('ddd')) .'"]\')')
                                            // ->whereRaw('json_contains(allowed_roles, \'["'. $role .'"]\')')
                                            // ->whereRaw('json_contains(allowed_roles, \'["customer"]\')')
                                            ->whereRaw('json_contains(allowed_roles, \'["POC Agent"]\')')
                                            ->orderBy('created_at', 'desc')
                                            ->where('status', 'approved')
                                            ->first();

                    if ($room_rate) {
                        $isDayAllowed = in_array(strtolower(Carbon::parse($date)->isoFormat('ddd')), $room_rate->days_interval);
                        $isDayExcluded = in_array($date, $room_rate->exclude_days);
                    }

                    $selling_rate = $room_type->rack_rate;

                    if ($room_rate && $isDayAllowed == true && $isDayExcluded == false) {
                        $selling_rate = $room_rate->room_rate;
                    }

                    $room_rate_total = $room_rate_total + $selling_rate;
                    /**
                     * End of room rate
                     */

                }

                // $available_rooms = count($diff->all()) == 0 ? (collect($items)->where('entity', $entity)->min('available')) : 0;
                // $available_rooms = count($diff->all()) == 0 ? count($available_rooms_per_room_type) : 0;
                $available_rooms = 0;

                if (count($diff->all()) == 0) {
                    
                    if ((collect($items)->where('entity', $entity)->min('available')) < count($available_rooms_per_room_type)) {
                        $available_rooms = (collect($items)->where('entity', $entity)->min('available'));
                    } else {
                        $available_rooms = count($available_rooms_per_room_type);
                    }

                }

                $room_types_with_allocations[] = [
                    'room_type_id' => $room_type_id,
                    'room_type' => $room_type,
                    'entity' => $entity,
                    'isAvailable' => count($diff->all()) == 0 ? true : false,
                    'room_allocations' => collect($items)->where('entity', $entity),
                    'available' => $available_rooms,
                    // 'sum1' => collect($items)->sum('allocation'), // 30
                    // 'sum2' => collect($items)->where('entity', $entity)->sum('allocation'), // 12
                    // 'available_with_room_blockings' => (collect($items)->sum('allocation') - count($room_blockings)) - collect($items)->where('entity', $entity)->sum('allocation'),
                    'room_rate_total' => $room_rate_total,
                    'available_rooms_per_room_type' => ($available_rooms_per_room_type),
                    // 'room_blockings_data' => ($room_blockings),
                    // 'test' => $room_allocations,
                    //'room_rate_total' => $room_type_not_in_list->rack_rate * count($period),
                ];
            }
        }

        return $room_types_with_allocations;
    }
}
