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

class ValidateCutOffTime
{
    public function checkIfPastCutOffTime($seatSegment)
    {
        $schedule = $seatSegment->schedule;

        if (!$schedule) {
            // Handle error when schedule not found
            return response()->json([
                'status' => 'failed',
                'error' => 'SCHEDULE_NOT_FOUND',
                'message' => 'No schedule was found.'
            ], 400);
        }
        
        // Parse trip_date into a Carbon instance
        $tripDate = Carbon::parse($seatSegment->schedule->trip_date);

        // Calculate cutoff time, which is one day before the trip date
        $cutoffDateTime = $tripDate->subDay()->setTime(23, 59, 59);

        if (Carbon::now() > $cutoffDateTime) {
            return response()->json([
                'error' => 'CUT_OFF_TIME_ERROR',
                'message' => 'Cut-off is the day before the trip at 11:59 PM.',
                'status' => 'failed',
            ], 400);
        }

        return null;
    }   

}