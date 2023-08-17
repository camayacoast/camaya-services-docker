<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\ActivityLog;
use App\Models\Hotel\RoomReservation;

class UpdateCheckInTime extends Controller
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
        if ($request->user()->user_type != 'admin') return response()->json([], 400);

        $room_reservation = RoomReservation::where('id', $request->id);

        if (!$room_reservation->first()) {
            return response()->json([], 404);
        }

        // Activity log
        // Create log
        ActivityLog::create([
            'booking_reference_number' => $room_reservation->first()->booking_reference_number,

            'action' => 'update_room_reservation_check_in_time',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has updated the check-in time from '. $room_reservation->first()->check_in_time .' to '. $request->time .'.',
            'model' => 'App\Models\Hotel\RoomReservation',
            'model_id' => $request->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        $room_reservation->update([
            'check_in_time' => $request->time
        ]);

        return response()->json(['message' => 'Updated check in time'], 200);

    }
}
