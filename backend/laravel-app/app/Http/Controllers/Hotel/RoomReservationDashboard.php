<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Carbon\Carbon;

use App\Models\Hotel\RoomReservation;
use App\Models\Booking\Guest;

class RoomReservationDashboard extends Controller
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
        $today = Carbon::now()->setTimezone('Asia/Manila')->format('Y-m-d');

        $guest_arrival = Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($query) use ($today) {
                $query->whereRaw("DATE_FORMAT(start_datetime, '%Y-%m-%d') = '".$today."'");
                $query->where('bookings.type', 'ON');
                $query->whereIn('bookings.status', ['confirmed', 'pending']);
            })
            ->select('guests.id')
            ->count();

        $guest_room_checked_in = Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($query) use ($today) {
                $query->whereRaw("DATE_FORMAT(start_datetime, '%Y-%m-%d') = '".$today."'");
                $query->where('bookings.type', 'ON');
                $query->whereIn('bookings.status', ['confirmed', 'pending']);
            })
            ->where('guests.status', 'room_checked_in')
            ->select('guests.id')
            ->count();

        /**
         * Departure
         */
        $guest_departure = Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($query) use ($today) {
                $query->whereRaw("DATE_FORMAT(end_datetime, '%Y-%m-%d') = '".$today."'");
                $query->where('bookings.type', 'ON');
                $query->whereIn('bookings.status', ['confirmed', 'pending']);
            })
            ->select('guests.id')
            ->count();
        
        return [
            'room_reservations_arrival' => $room_reservations_arrival ?? 0,

            'total_arrival_guest_count' => $guest_arrival,
            'checked_in_guests_count' => $guest_room_checked_in,
            // 'total_arrival_infant_guest_count' => $total_arrival_infant_guest_count,

            'total_departure_guest_count' => $guest_departure,
        ];
    }
}
