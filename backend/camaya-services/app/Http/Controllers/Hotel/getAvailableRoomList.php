<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\Room;
use App\Models\Hotel\RoomType;
use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomAllocation;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class getAvailableRoomList extends Controller
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

        $arrival_date = $request->arrival_date." 12:00:00";
        $departure_date = $request->departure_date." 11:00:00";

        // Get booked rooms
        $booked_rooms = RoomReservation::where(function ($query) use ($arrival_date, $departure_date) {
                                        $query->where(function ($query) use ($arrival_date, $departure_date) {
                                            $query->where('start_datetime', '<=', $arrival_date)
                                                ->where('end_datetime', '>=', $arrival_date);
                                        })->orWhere(function ($query) use ($arrival_date, $departure_date) {
                                            $query->where('start_datetime', '<=', $departure_date)
                                                ->where('end_datetime', '>=', $departure_date);
                                        })->orWhere(function ($query) use ($arrival_date, $departure_date) {
                                            $query->where('start_datetime', '>=', $arrival_date)
                                                ->where('end_datetime', '<=', $departure_date);
                                        });
                                    })
                                    ->whereIn('status', ['confirmed', 'pending', 'checked_in', 'checked_out', 'blackout'])
                                    ->pluck('room_id');

        $available_rooms = Room::select('id', 'property_id', 'room_type_id', 'room_status', 'number', 'description')
                    ->where('enabled', 1)
                    ->whereNotIn('id', $booked_rooms)
                    ->with('type.images')
                    ->with('property')
                    ->get();

        // Get room rate totals



        // Get room type allocations per date

        $room_allocations = RoomReservation::roomAllocationForBooking([
            'user' =>  $request->user(),
            'arrival' => $request->arrival_date,
            'departure' => $request->departure_date,
        ]);

        foreach ($available_rooms as $key => $room) {

            $room_type = collect($room_allocations)->firstWhere('room_type_id', $room['room_type_id']);

            $room_allocation = [];

            foreach ($room_allocations as $key2 => $ra) {
                if ($ra['room_type_id'] == $room['room_type_id']) {
                    $room_allocation[] = [
                        'room_type_id' => $ra['room_type_id'],
                        'entity' => $ra['entity'],
                        'available' => $ra['available'],
                        'isAvailable' => $ra['isAvailable'],
                    ];
                }                
            }

            if ($room_type) {
                $available_rooms[$key]['room_rate_total'] = $room_type['room_rate_total'];

                // Set room allocation per room type
                $available_rooms[$key]['room_allocations'] = $room_allocation;
            }
            
        }

        $room_types = RoomType::whereIn('id', collect($available_rooms)->pluck('room_type_id')->all())->with('property')->get();

        foreach ($room_types as $key => $room_type) {

            $room_allocation = [];

            foreach ($room_allocations as $key2 => $ra) {
                if ($ra['room_type_id'] == $room_type['id']) {
                    $room_allocation[] = [
                        'room_type_id' => $ra['room_type_id'],
                        'entity' => $ra['entity'],
                        'available' => $ra['available'],
                        'isAvailable' => $ra['isAvailable'],
                        'taken' => 0,
                    ];
                }                
            }

            $room_types[$key]['room_allocations'] = $room_allocation;

        }

        return [
            'available_rooms' => collect($available_rooms)->groupBy('type.code')->all(),
            'room_types' => $room_types,
            // 'room_allocations' => $room_allocations,
        ];
    }
}
