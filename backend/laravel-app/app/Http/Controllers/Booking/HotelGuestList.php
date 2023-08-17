<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;
use App\Models\Hotel\RoomAllocation;
use Carbon\Carbon;

class HotelGuestList extends Controller
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
        $guests = Guest::whereHas('booking', function ($query) use ($request) {
            $query->whereDate('start_datetime', '<=', $request->date)
                ->whereDate('end_datetime', '>=', $request->date);
            $query->where('type', 'ON');
            $query->whereIn('bookings.status', ['confirmed', 'pending']);
        })
        ->whereNotIn('status', ['no_show', 'booking_cancelled'])
        ->with(['booking' => function ($q) {
            $q->with(['room_reservations' => function ($q) {
                // $q->join('rooms','room_reservations.room_id','=','rooms.id');
                // $q->select('room_reservations.*', 'rooms.number');
                $q->with('room.property');
            }]);
        }])
        ->with('passes')
        ->get();

        $guests_with_market_segmentation = [];

        foreach ($guests as $guest) {

            $market_segmentation = [];
            if (isset($guest['booking']['room_reservations'])) {
                foreach ($guest['booking']['room_reservations'] as $room_reservation) {

                    foreach ($room_reservation['allocation_used'] as $allocation_used) {
                        $room_allocation = RoomAllocation::where('id', $allocation_used)->first();

                        $market_segmentation[] = $room_allocation['entity'];
                    }
                    
                }
            }

            $guests_with_market_segmentation[] = [
                'booking' => $guest['booking'],
                'passes' => $guest['passes'],
                'booking_reference_number' => $guest['booking_reference_number'],
                'reference_number' => $guest['reference_number'],
                'first_name' => $guest['first_name'],
                'last_name' => $guest['last_name'],
                'age' => $guest['age'],
                'type' => $guest['type'],
                'nationality' => $guest['nationality'],
                'status' => $guest['status'],
                'market_segmentation' => collect($market_segmentation)->unique()->values()->all(),
            ];
        }

        return $guests_with_market_segmentation;
    }
}
