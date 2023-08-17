<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Carbon\Carbon;

use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\Room;

class RoomBlocking extends Controller
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
        $start_date = Carbon::parse($request->dates[0])->setTimezone('Asia/Manila')->format('Y-m-d');
        $start_time = Carbon::parse($request->dates[0])->setTimezone('Asia/Manila')->format('H:i:s');

        $end_date = Carbon::parse($request->dates[count($request->dates)-1])->setTimezone('Asia/Manila')->format('Y-m-d');
        $end_time = Carbon::parse($request->dates[count($request->dates)-1])->setTimezone('Asia/Manila')->format('H:i:s') == '00:00:00' ? '11:00:00' : '23:59:00';

        // return [
        //     $start_date,
        //     $start_time,
        //     $end_date,
        //     $end_time
        // ];
        $arrival = $start_date." ".$start_time;
        $departure = $end_date." ".$end_time;

        // $room = Room::with(['property' => function ($q) use ($request) {
        //                 $q->where('code', $request->property_code);
        //             }])
        //             ->where('number', $request->room_number)
        //             ->first();
        $room = Room::where('id', $request->room_id)
                        ->with('property')
                        ->first();

        if (!$room) {
            return response()->json(['error' => 'ROOM_NOT_FOUND'], 400);
        }

        // Check if not booked
        $exists = RoomReservation::where(function ($query) use ($arrival, $departure) {
                            $query->where(function ($query) use ($arrival, $departure) {
                                $query->where('start_datetime', '<=', $arrival)
                                    ->where('end_datetime', '>=', $arrival);
                            })->orWhere(function ($query) use ($arrival, $departure) {
                                $query->where('start_datetime', '<=', $departure)
                                    ->where('end_datetime', '>=', $departure);
                            })->orWhere(function ($query) use ($arrival, $departure) {
                                $query->where('start_datetime', '>=', $arrival)
                                    ->where('end_datetime', '<=', $departure);
                            });
                        })
                        ->where('room_id', $room->id)
                        ->whereIn('status', ['confirmed', 'pending', 'checked_in', 'checked_out', 'blackout'])
                        ->first();
        
        if ($exists) {
            return response()->json(['error' => 'ROOM_ALREADY_BOOKED'], 400);
        }

        $blocking_reference_number = "BO-".\Str::upper(\Str::random(6));

        // Creates a new reference number if it encounters duplicate
        while (RoomReservation::where('booking_reference_number', $blocking_reference_number)->exists()) {
            $blocking_reference_number = "BO-".\Str::upper(\Str::random(6));
        }

        $newRoomReservation = RoomReservation::create([
            'room_id' => $room->id,
            'room_type_id' => $room->room_type_id,
            'booking_reference_number' => $blocking_reference_number,
            'category' => 'blocking',
            'status' => 'blackout',
            'description' => $request->description,
            // 'allocation_used' => NULL,
            'start_datetime' => $start_date." ".$start_time,
            'end_datetime' => $end_date." ".$end_time,
            'created_by' => $request->user()->id
        ]);

        return $newRoomReservation;


        
    }
}
