<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\BookingTag;
use App\Models\Booking\Booking;
use App\Models\Booking\ActivityLog;
use Carbon\Carbon;

class UpdateBookingDate extends Controller
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

        // if (env('APP_ENV') == 'production') {
        //     return false;
        // }

        $booking = Booking::where('reference_number', $request->booking_reference_number)->first();

        if(!$booking) {
            return response()->json(['error' => 'BOOKING_NOT_FOUND'], 400);
        }

        //
        /**
         * Update booking date
         */

        $booking->start_datetime = Carbon::create($request->start_datetime);
        $booking->end_datetime = Carbon::create($request->end_datetime);
        $booking->save();

        // Create log
        // use App\Models\Booking\ActivityLog;
        ActivityLog::create([
            'booking_reference_number' => $request->booking_reference_number,

            'action' => 'update_booking_date',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has updated the booking date: start_datetime '.$request->start_datetime.', end_datetime: '.$request->end_datetime,
            'model' => 'App\Models\Booking\Booking',
            'model_id' => $booking->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        return 'OK';
    }
}
