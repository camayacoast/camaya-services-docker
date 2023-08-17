<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\Room;

class AvailableRoomsForDate extends Controller
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
        $date = $request->date;

        $booked_rooms = RoomReservation::where(function ($query) use ($date) {
                            // $query->where(function ($query) use ($date) {
                            //     $query->where('start_datetime', '<=', $date)
                            //         ->where('end_datetime', '>=', $date);
                            // })->orWhere(function ($query) use ($date) {
                            //     $query->where('start_datetime', '<=', $date)
                            //         ->where('end_datetime', '>=', $date);
                            // })->orWhere(function ($query) use ($date) {
                            //     $query->where('start_datetime', '>=', $date)
                            //         ->where('end_datetime', '<=', $date);
                            // });
                            $query->where(function ($query) use ($date) {
                                    $query->whereDate('start_datetime', '<=', $date)
                                        ->whereDate('end_datetime', '>', $date);
                            });
                        })
                        ->whereIn('status', ['confirmed', 'pending', 'checked_in', 'checked_out', 'blackout'])
                        // ->get();
                        ->pluck('room_id');

        $available_rooms = Room::whereNotIn('id', $booked_rooms)
                            ->where('enabled', 1)
                            ->with('type:id,name')
                            ->with('property:id,code')
                            ->get();

        $room_allocations = RoomAllocation::where('date', $request->date)
                                ->where('entity', $request->entity)
                                ->get();

        $available_room_allocations = [];

        foreach ($available_rooms as $available_room) {

            $room_allocation = collect($room_allocations)->firstWhere('room_type_id', $available_room['room_type_id']);

            $available_room_allocations[] = [
                'room_id' => $available_room['id'],
                'room_number' => $available_room['number'],
                'room_type_name' => $available_room['type']['name'],
                'property_code' => $available_room['property']['code'],
                'remaining' => $room_allocation ? $room_allocation['allocation'] - $room_allocation['used'] : 0,
            ];
        }
        
        return $available_room_allocations;
    }
}
