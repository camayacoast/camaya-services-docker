<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\Room;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomReservation;

use App\Models\Booking\ActivityLog;
use App\Models\Booking\Customer;

use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SwitchRoom extends Controller
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

        $arrival = $request->room_reservation['date_of_arrival']." ".$request->room_reservation['check_in_time'];
        $departure = $request->room_reservation['date_of_departure']." ".$request->room_reservation['check_out_time'];

        $period = CarbonPeriod::create($request->room_reservation['date_of_arrival'], $request->room_reservation['date_of_departure']);
        $dates_period = [];

        foreach ($period as $date_period) {
            if ($date_period->format('Y-m-d') != Carbon::parse($request->room_reservation['date_of_departure'])->format('Y-m-d')) {
                $dates_period[] = $date_period->format('Y-m-d');
            }
        }

        $room_reservation = RoomReservation::find($request->room_reservation['id']);

        if (!$room_reservation) {
            return response()->json(['error' => 'ROOM_RESERVATION_NOT_FOUND'], 400);
        }

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
                            ->where('room_id', $request->room_id)
                            ->whereIn('status', ['confirmed', 'pending', 'checked_in', 'checked_out', 'blackout'])
                            ->first();

        if ($exists) {
            return response()->json(['error' => 'ROOM_ALREADY_BOOKED'], 400);
        }

        
        /**
         * Update room allocation 
         */ 
        $room = Room::where('id', $request->room_id)->with('type')->first();

        // Update origin room allocation used to return the allocation
        RoomAllocation::whereIn('id', $room_reservation->allocation_used)
                        ->decrement('used');

        // Get room allocation of the destination room
        $room_allocation = RoomAllocation::whereIn('id', $room_reservation->allocation_used)->first();
        $destination_room_allocation = RoomAllocation::where('room_type_id', $room->room_type_id)
                                                    ->where('entity', $room_allocation->entity)
                                                    ->where('status', 'approved')
                                                    // date range
                                                    ->whereIn('date', $dates_period)
                                                    ->select(
                                                        'allocation',
                                                        'used',
                                                        'allowed_roles',
                                                        'date',
                                                        'entity',
                                                        'id',
                                                        'room_type_id',
                                                        'status',
                                                        \DB::raw("`allocation` - `used` as available")
                                                    )
                                                    ->get();
                                                    
        \Log::info($room);
        // Check if destination room allocation is available to use
        if (collect($destination_room_allocation)->min('available') <= 0) {
            // Update origin room allocation used to return the allocation
            RoomAllocation::whereIn('id', $room_reservation->allocation_used)
                        ->increment('used');
            return response()->json(['error' => 'ROOM_ALLOCATION_NOT_AVAILABLE', 'message' => '['.$room_allocation->entity.'] Room allocation not available for the destination room.'], 400);
        }

        // return collect($destination_room_allocation)->min('available');

        // Increment the allocation to be used on the room on the new room transferred
        RoomAllocation::whereIn('id', collect($destination_room_allocation)->pluck('id')->all())
                    ->increment('used');

        $room_reservation
            ->load('room')
            ->load('room_type');

        // Create log
        ActivityLog::create([
            'booking_reference_number' => $room_reservation->booking_reference_number,

            'action' => 'switch_room',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has switched the room from '. $room_reservation->room->number .' ('.$room_reservation->room_type->name.') to '.$room->number.' ('.$room->type->name.').',
            'model' => 'App\Models\Hotel\RoomReservation',
            'model_id' => $room_reservation->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        /**
         * Update room_id of the room reservation
         */
        $room_reservation->update([
            'room_id' => $request->room_id,
            'room_type_id' => $room->room_type_id,
            'allocation_used' => collect($destination_room_allocation)->pluck('id')->all()
        ]);
        
        return RoomReservation::where('room_reservations.id', $room_reservation->id)
                        ->leftJoin('bookings', 'room_reservations.booking_reference_number','=','bookings.reference_number')
                        ->select(
                            'room_reservations.id',
                            'room_reservations.booking_reference_number',
                            // 'customer_first_name', /
                            // 'customer_last_name', /
                            'room_id',
                            DB::raw("DATE_FORMAT(room_reservations.start_datetime, '%Y-%m-%d') as date_of_arrival"),
                            DB::raw("DATE_FORMAT(room_reservations.start_datetime, '%H:%i') as check_in_time"),
                            DB::raw("DATE_FORMAT(room_reservations.end_datetime, '%Y-%m-%d') as date_of_departure"),
                            DB::raw("DATE_FORMAT(room_reservations.end_datetime, '%H:%i') as check_out_time"),
                            // 'check_in_time',
                            // 'date_of_departure',
                            // 'check_out_time',
                            // 'stay_duration',
                            'room_reservations.status',
                            'room_reservations.category',
                            'room_reservations.description',

                            'bookings.status as booking_status',
                        )
                        ->addSelect([
                            'customer_first_name' => Customer::whereColumn('id', 'bookings.customer_id')
                                                // ->select('first_name', 'last_name')
                                                ->select('first_name')
                                                ->limit(1)
                            ,
                            'customer_last_name' => Customer::whereColumn('id', 'bookings.customer_id')
                                                // ->select('first_name', 'last_name')
                                                ->select('last_name')
                                                ->limit(1)
                        ])->first();
        
    }
}
