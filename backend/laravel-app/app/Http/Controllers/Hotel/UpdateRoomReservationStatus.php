<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Customer;

use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\Room;

use Illuminate\Support\Facades\DB;

class UpdateRoomReservationStatus extends Controller
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

        $room_reservation = RoomReservation::find($request->room_reservation['id']);

        if (!$room_reservation) {
            return response()->json(['error' => 'ROOM_RESERVATION_NOT_FOUND'], 400);
        }

        $room_reservation->update([
            'status' => $request->reservation_status,
        ]);

        if ($request->reservation_status == 'checked_out') {
            $room_reservation->update([
                'check_out_time' => isset($request->data['check_out_time']) ? $request->data['check_out_time'] : now(),
                'checked_out_by' => $request->user()->id,
            ]);

            if ($request->room_reservation['date_of_departure'] == now()->format('Y-m-d')) {
                Room::where('id', $room_reservation->room_id)
                        ->update([
                            'fo_status' => isset($request->data['out_room_fo_status']) ? $request->data['out_room_fo_status'] : 'vacant',
                            'room_status' => 'dirty',
                        ]);
            }
        }

        /**
         * Checked-in
         */
        if ($request->reservation_status == 'checked_in') {
            $room_reservation->update([
                'check_in_time' => isset($request->data['check_in_time']) ? $request->data['check_in_time'] : now(),
                'checked_in_by' => $request->user()->id,
            ]);
            if ($request->room_reservation['date_of_arrival'] == now()->format('Y-m-d')) {
                Room::where('id', $room_reservation->room_id)
                        ->update([
                            'fo_status' => isset($request->data['in_room_fo_status']) ? $request->data['in_room_fo_status'] : 'occupied',
                        ]);
            }
        }

        if ($request->reservation_status == 'cancelled') {

            $room_reservations_allocation_used = RoomReservation::where('id', $request->room_reservation['id'])
                                    ->pluck('allocation_used');

            foreach ($room_reservations_allocation_used as $value) {
                RoomAllocation::whereIn('id', $value)
                        ->decrement('used');
            }

        }

        $user = DB::connection('mysql')->table('users')->where('id', $room_reservation['created_by'])->select('id', 'first_name', 'last_name')->first();

        $updated_room_res = RoomReservation::where('room_reservations.id', $room_reservation->id)
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
                            'room_reservations.check_in_time as check_in_datetime',
                            'room_reservations.check_out_time as check_out_datetime',
                            // 'check_in_time',
                            // 'date_of_departure',
                            // 'check_out_time',
                            // 'stay_duration',
                            'room_reservations.allocation_used',
                            'room_reservations.status',
                            'room_reservations.category',
                            'room_reservations.description',

                            'bookings.status as booking_status',
                            'bookings.adult_pax as adult_pax',
                            'bookings.kid_pax as kid_pax',
                            'bookings.infant_pax as infant_pax',
                            'room_reservations.created_by',
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
                        ])
                        ->with(['room' => function ($q) {
                            $q->select('id','room_type_id','number');
                            $q->with('type:id,name');
                        }])
                        ->first();

        $_arrival_date = $updated_room_res['date_of_arrival']." ".$updated_room_res['check_in_time'];
        $_departure_date = $updated_room_res['date_of_departure']." ".$updated_room_res['check_out_time'];

        $booked_rooms = RoomReservation::where(function ($query) use ($_arrival_date, $_departure_date) {
                            $query->where(function ($query) use ($_arrival_date, $_departure_date) {
                                $query->where('start_datetime', '<=', $_arrival_date)
                                    ->where('end_datetime', '>=', $_arrival_date);
                            })->orWhere(function ($query) use ($_arrival_date, $_departure_date) {
                                $query->where('start_datetime', '<=', $_departure_date)
                                    ->where('end_datetime', '>=', $_departure_date);
                            })->orWhere(function ($query) use ($_arrival_date, $_departure_date) {
                                $query->where('start_datetime', '>=', $_arrival_date)
                                    ->where('end_datetime', '<=', $_departure_date);
                            });
                        })
                        ->whereIn('status', ['confirmed', 'pending', 'checked_in', 'checked_out', 'blackout'])
                        ->pluck('room_id');

        $available_rooms = Room::whereNotIn('id', $booked_rooms)
                        ->where('enabled', 1)
                        // ->whereNotIn('room_status', ['out-of-order', 'out-of-service'])
                        // ->whereIn('room_status', ['clean', 'dirty', 'pickup', 'sanitized', 'inspected'])
                        // ->orWhereNull('room_status')
                        ->with('property')
                        ->with('type')
                        ->get();

        
        $room_allocation_used = RoomAllocation::whereIn('id', isset($updated_room_res->allocation_used) ? $updated_room_res->allocation_used : [])->pluck('entity');

            return [
                'id' => $updated_room_res['id'],
                'booking_reference_number' => $updated_room_res['booking_reference_number'],
                'room_id' => $updated_room_res['room_id'],
                'date_of_arrival' => $updated_room_res['date_of_arrival'],
                'check_in_time' => $updated_room_res['check_in_time'],
                'date_of_departure' => $updated_room_res['date_of_departure'],
                'check_out_time' => $updated_room_res['check_out_time'],
                'status' => $updated_room_res['status'],
                'category' => $updated_room_res['category'],
                'description' => $updated_room_res['description'],
                'booking_status' => $updated_room_res['booking_status'],
                'customer_first_name' => $updated_room_res['customer_first_name'],
                'customer_last_name' => $updated_room_res['customer_last_name'],
                'created_at' => $updated_room_res['created_at'],
                'available_rooms' => $available_rooms,
                'market_segmentation' => $room_allocation_used,
                'allocation_used' => $updated_room_res['allocation_used'],

                'room' => $updated_room_res['room'],

                'adult_pax' => $updated_room_res['adult_pax'],
                'kid_pax' => $updated_room_res['kid_pax'],
                'infant_pax' => $updated_room_res['infant_pax'],

                'booked_by' => $user,

                'check_in_datetime' => $updated_room_res['check_in_datetime'],
                'check_out_datetime' => $updated_room_res['check_out_datetime'],
            ];


    }
}
