<?php

namespace App\Http\Controllers\OneBITS\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Trip;
use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Schedule;
use App\Models\Transportation\Passenger;

use Carbon\Carbon;

class BoardPassengerTrip extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $ticket = Ticket::where('reference_number', $request->reference_number)->first();
        
        if (! $ticket) {
            return response()->json(['message' => 'Could not find ticket.'], 402);
        }

        // Check if paid
        if ($ticket->status != 'paid') {
            return response()->json(['message' => 'Ticket not yet paid.'], 402);
        }

        $schedule = Schedule::where('trip_number', $ticket->trip_number)->first();
        // Check if schedule is cancelled
        if ($schedule->status === 'cancelled') {
            return response()->json(['message' => 'Schedule is cancelled'], 402);
        }

        $trip = Trip::where('ticket_reference_number', $request->reference_number)
                    ->where('trip_number', $ticket->trip_number)
                    ->first();

        // Check if trip is cancelled
        if ($trip->status === 'cancelled' || $trip->status === 'no_show') {
            return response()->json(['message' => 'Passenger trip is cancelled'], 402);
        }

        if ($trip->status === 'boarded') {
            return response()->json(['message' => 'Passenger already boarded'], 402);
        }
        
        Trip::where('ticket_reference_number', $request->reference_number)->update([
            'status' => 'boarded',
            'boarded_at' => Carbon::now(),
        ]);

        $passenger = Passenger::where('id', $ticket->passenger_id)
                     ->where('trip_number', $ticket->trip_number)
                     ->first();
        
        return response()->json(['passenger' => $passenger, 'message' => 'Successfully updated to Boarded'], 200);
      
    }
}
