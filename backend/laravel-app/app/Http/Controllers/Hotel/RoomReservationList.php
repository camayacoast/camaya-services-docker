<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\Room;
use App\Models\Hotel\RoomAllocation;

use App\User;
use App\Models\Booking\Customer;
use App\Models\Booking\Booking;

use Illuminate\Support\Facades\DB;

class RoomReservationList extends Controller
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
        $arrival = $request->start_date." 00:00:00";
        $departure = $request->end_date." 23:59:59";
        $filter_customer_booking_code = $request->query('customerBookingCode', false);

        $room_reservations = RoomReservation::where(function ($query) use ($arrival, $departure) {
                                    $query->where(function ($query) use ($arrival, $departure) {
                                        $query->where('room_reservations.start_datetime', '<=', $arrival)
                                            ->where('room_reservations.end_datetime', '>=', $arrival);
                                    })->orWhere(function ($query) use ($arrival, $departure) {
                                        $query->where('room_reservations.start_datetime', '<=', $departure)
                                            ->where('room_reservations.end_datetime', '>=', $departure);
                                    })->orWhere(function ($query) use ($arrival, $departure) {
                                        $query->where('room_reservations.start_datetime', '>=', $arrival)
                                            ->where('room_reservations.end_datetime', '<=', $departure);
                                    });
                                })
                                ->whereIn('room_reservations.status', ['confirmed', 'pending', 'blackout', 'checked_in', 'checked_out'])
                                // ->with('booking')
                                ->leftJoin('bookings', 'room_reservations.booking_reference_number','=','bookings.reference_number')
                                // ->whereIn('bookings.status', ['confirmed', 'pending'])
                                // ->leftJoin(env('DB_DATABASE').'.users as db2','room_reservations.created_by','=','db2.id')
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
                                    // 'db2.first_name as booked_by_first_name',
                                    // 'db2.last_name as booked_by_last_name',
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
                                    // ,
                                    // 'booked_by_last_name' => DB::connection('mysql')->table('users')->whereColumn('users.id', 'room_reservations.created_by')
                                    //                     ->select('users.last_name')
                                    //                     ->limit(1)
                                    // ,
                                    // 'booked_by_first_name' => User::whereColumn('users.id', 'room_reservations.created_by')
                                    //                     ->select('users.first_name')
                                    //                     ->limit(1)
                                ])
                                ->with(['room' => function ($q) {
                                    $q->select('id','room_type_id','number');
                                    $q->with('type:id,name');
                                }])
                                // ->with('booked_by:id,first_name,last_name')
                                ->get();

        /**
         * Get users
         */
        $users = DB::connection('mysql')->table('users')->whereIn('id', collect($room_reservations)->pluck('created_by')->all())->select('id', 'first_name', 'last_name')->get();

        $room_reservations_with_available_switch_room = [];


        foreach ($room_reservations as $room_reservation) {

            $_arrival_date = $room_reservation['date_of_arrival']." ".$room_reservation['check_in_time'];
            $_departure_date = $room_reservation['date_of_departure']." ".$room_reservation['check_out_time'];

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

            
            $room_allocation_used = RoomAllocation::whereIn('id', isset($room_reservation->allocation_used) ? $room_reservation->allocation_used : [])->pluck('entity');

            if ($filter_customer_booking_code) {
                $customer_name = $room_reservation['customer_first_name'] . ' ' . $room_reservation['customer_last_name'];
                $booking_reference_number = $room_reservation['booking_reference_number'];
                if (strtoupper($filter_customer_booking_code) == $booking_reference_number 
                    || preg_match("/".$filter_customer_booking_code."/i", $customer_name)) {
                        $room_reservations_with_available_switch_room[] = [
                            'id' => $room_reservation['id'],
                            'booking_reference_number' => $room_reservation['booking_reference_number'],
                            'room_id' => $room_reservation['room_id'],
                            'date_of_arrival' => $room_reservation['date_of_arrival'],
                            'check_in_time' => $room_reservation['check_in_time'],
                            'date_of_departure' => $room_reservation['date_of_departure'],
                            'check_out_time' => $room_reservation['check_out_time'],
                            'status' => $room_reservation['status'],
                            'category' => $room_reservation['category'],
                            'description' => $room_reservation['description'],
                            'booking_status' => $room_reservation['booking_status'],
                            'customer_first_name' => $room_reservation['customer_first_name'],
                            'customer_last_name' => $room_reservation['customer_last_name'],
                            'created_at' => $room_reservation['created_at'],
                            'available_rooms' => $available_rooms,
                            'market_segmentation' => $room_allocation_used,
                            'allocation_used' => $room_reservation['allocation_used'],
            
                            'room' => $room_reservation['room'],
            
                            'adult_pax' => $room_reservation['adult_pax'],
                            'kid_pax' => $room_reservation['kid_pax'],
                            'infant_pax' => $room_reservation['infant_pax'],

                            'booked_by' => collect($users)->firstWhere('id', $room_reservation['created_by']) ?? [],
                            // 'booked_by_last_name' => $room_reservation['booked_by_last_name'],
                            
                            'check_in_datetime' => $room_reservation['check_in_datetime'],
                            'check_out_datetime' => $room_reservation['check_out_datetime'],
                            // 'booked_by' => $room_reservation['booked_by'],
                        ];
                }
            } else {
                $room_reservations_with_available_switch_room[] = [
                    'id' => $room_reservation['id'],
                    'booking_reference_number' => $room_reservation['booking_reference_number'],
                    'room_id' => $room_reservation['room_id'],
                    'date_of_arrival' => $room_reservation['date_of_arrival'],
                    'check_in_time' => $room_reservation['check_in_time'],
                    'date_of_departure' => $room_reservation['date_of_departure'],
                    'check_out_time' => $room_reservation['check_out_time'],
                    'status' => $room_reservation['status'],
                    'category' => $room_reservation['category'],
                    'description' => $room_reservation['description'],
                    'booking_status' => $room_reservation['booking_status'],
                    'customer_first_name' => $room_reservation['customer_first_name'],
                    'customer_last_name' => $room_reservation['customer_last_name'],
                    'created_at' => $room_reservation['created_at'],
                    'available_rooms' => $available_rooms,
                    'market_segmentation' => $room_allocation_used,
                    'allocation_used' => $room_reservation['allocation_used'],
    
                    'room' => $room_reservation['room'],
    
                    'adult_pax' => $room_reservation['adult_pax'],
                    'kid_pax' => $room_reservation['kid_pax'],
                    'infant_pax' => $room_reservation['infant_pax'],

                    'booked_by' => collect($users)->firstWhere('id', $room_reservation['created_by']) ?? [],
                    // 'booked_by_last_name' => $room_reservation['booked_by_last_name'],

                    'check_in_datetime' => $room_reservation['check_in_datetime'],
                    'check_out_datetime' => $room_reservation['check_out_datetime'],
                    // 'booked_by' => $room_reservation['booked_by'],
                ];
            }
        }


        return $room_reservations_with_available_switch_room;
    }
}
