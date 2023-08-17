<?php

namespace App\Http\Controllers\OneBITS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;
use App\Models\Transportation\Trip;

use App\Models\Transportation\Passenger;
use App\Models\Transportation\SeatSegment;
use App\Models\OneBITS\Ticket;

use Carbon\Carbon;

class CancelPassengerTrip extends Controller
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

        Trip::where('ticket_reference_number', $request->reference_number)->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
        ]);

        Ticket::where('reference_number', $request->reference_number)->update([
            'status' => 'cancelled',
        ]);

        /**
         * Seat segment decrement
         */
        $trip = Trip::where('ticket_reference_number', $request->reference_number)->first();

        $guest = Passenger::where('id', $trip->passenger_id)->first();

        if ($guest->type != 'infant') {
            SeatSegment::where('id', $trip->seat_segment_id)
                ->decrement('used');
        }

        return 'OK';
    }
}
