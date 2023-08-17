<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\ActivityLog;

use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\Room;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RoomTransfer extends Controller
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
        $end_date = Carbon::parse($date)->add(1, 'day')->format('Y-m-d');

        $arrival_date = $request->room_data['date_of_arrival'];
        $departure_date = $request->room_data['date_of_departure'];
        
        $day_before_departure_date = Carbon::parse($departure_date)->subtract(1, 'day')->format('Y-m-d');

        $period = Carbon::parse($arrival_date)->daysUntil($day_before_departure_date);

        $date_period = [];
        $room_reservations = [];

        // return $request->room_data;
        $room_reservation_to_transfer = null;

        foreach ($period as $key => $_date) {
            $date_period[$key] = $_date->format('Y-m-d');

            // Split here
            $split_room_reservations = RoomReservation::create([
                // 'room_id' => ..., // Set room ID for auto check-in; Either random or by first available number
                'room_id' => $request->room_data['room_id'],
                'room_type_id' => $request->room_data['room']['room_type_id'],
                'booking_reference_number' => $request->room_data['booking_reference_number'],
                'category' => 'booking',
                'status' => $date_period[$key] >= $request->date ? 'confirmed' : $request->room_data['status'],
                // 'status' => 'confirmed', // set to confirmed only after transfer
                'check_in_time' => $request->room_data['status'] == 'checked_in' && $date_period[$key] < $request->date ? $request->room_data['check_in_datetime'] : null,

                'start_datetime' => $_date->format('Y-m-d')." 12:00:00",
                'end_datetime' => $_date->add(1, 'day')->format('Y-m-d')." 11:00:00",
    
                'allocation_used' => [$request->room_data['allocation_used'][$key]],
            ]);

            if ($date_period[$key] == $request->date) {
                $room_reservation_to_transfer = $split_room_reservations;
            }
            
        }

        // Split the existing room_reservation
        // return $date_period;
        
        // Check if room is available
        $isRoomBooked = RoomReservation::where(function ($query) use ($request) {
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
                            $query->where(function ($query) use ($request) {
                                    $query->whereDate('start_datetime', '<=', $request->date)
                                        ->whereDate('end_datetime', '>', $request->date);
                            });
                        })
                        ->whereIn('status', ['confirmed', 'pending', 'checked_in', 'checked_out', 'blackout'])
                        ->where('room_id', $request->room_id)
                        ->first();

        if ($isRoomBooked) {
            return response()->json(['error' => 'ROOM_IS_BOOKED'], 400);
        }

        $room = Room::where('id', $request->room_id)->with('type:id,name')->first();

        // Check if room_allocation is available
        $room_allocation = RoomAllocation::where('entity', $request->room_data['market_segmentation'][0])
                                ->where('room_type_id', $room->room_type_id)
                                ->whereDate('date', $request->date)
                                ->first();

        $available = $room_allocation->allocation - $room_allocation->used;

        if ($available <= 0) {
            return response()->json(['error' => 'ROOM_ALLOCATION_DEPLETED'], 400);
        }

        // Create new room_reservation record
        $updateRoomReservation = RoomReservation::where('id', $room_reservation_to_transfer['id'])
            ->update([
                'room_id' => $request->room_id,
                'room_type_id' => $room['room_type_id'],
                // 'booking_reference_number' => $request->room_data['booking_reference_number'],
                // 'category' => 'booking',
                // 'status' => 'pending',
                // 'start_datetime' => $date." 12:00:00",
                // 'end_datetime' => $end_date." 11:00:00",

                'allocation_used' => [$room_allocation->id],
            ]);

        // Update room allocation

        // Decrement release allocation
        RoomAllocation::where('id', $room_reservation_to_transfer['allocation_used'][0])->decrement('used');

        // Increment taken allocation
        RoomAllocation::where('id', $room_allocation->id)->increment('used');

        // Cancelled previous room reservation
        RoomReservation::where('id', $request->room_data['id'])
                ->update([
                    'status' => 'transferred',
                ]);

        // Log room transfer

        // Create log
        ActivityLog::create([
            'booking_reference_number' => $request->room_data['booking_reference_number'],

            'action' => 'transfer_room',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has transferred the room from '. $request->room_data['room']['number'] .' ('.$request->room_data['room']['type']['name'].') to '.$room->number.' ('.$room->type->name.').',
            'model' => 'App\Models\Hotel\RoomReservation',
            'model_id' => $request->room_data['id'],
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        // Done
        return 'OK';
    }
}
