<?php

namespace App\Http\Controllers\OneBITS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Passenger;
use App\Models\Transportation\Trip;
use App\Models\Transportation\Schedule;
use App\Models\Transportation\SeatSegment;
use App\Models\Transportation\Seat;

use DB;
use Carbon\Carbon;

class ValidateIfPassengerExists
{
    public function checkIfPassengersExist($seat_segment_id, $passengers, $connection)
    {
        // Obtain the specific SeatSegment based on the incoming request
        $seatSegment = SeatSegment::find($seat_segment_id);

        if (!$seatSegment) {
            $connection->rollBack();
            return response()->json([
                'error' => 'SEAT_SEGMENT_NOT_FOUND',
                'message' => 'No seat segment was found.'
            ], 400);
        }

        // Fetch the associated schedule
        $tripSchedule = $seatSegment->schedule;

        $duplicatePassengers = [];

        foreach ($passengers as $passenger) {

            $pendingBookingExists = Ticket::whereHas('trip.schedule', function ($query) use ($tripSchedule) {
                $query->where('id', $tripSchedule->id);
            })
            ->whereHas('passenger', function ($query) use ($passenger) {
                $query->where('first_name', strtoupper($passenger['first_name']))
                    ->where('last_name', strtoupper($passenger['last_name']));
            })
            ->whereIn('status', ['created', 'pending', 'paid'])
            ->exists();

            if ($pendingBookingExists) {
                $duplicatePassengers[] = $passenger;
            }
        }

        if (count($duplicatePassengers) > 0) {
            $connection->rollBack();
            return response()->json([
                'error' => 'PENDING_BOOKING_EXISTS',
                'message' => 'Pending booking transaction is existing.',
                'status' => 'failed',
                'duplicate_passengers' => $duplicatePassengers
            ], 400);
        }

        // If no duplicate passengers were found, return null
        return null;
    }   
}