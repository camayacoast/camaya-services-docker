<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;
use App\Models\Booking\ActivityLog;

use Carbon\Carbon;

class UpdateAutoCancelDate extends Controller
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

        $booking = Booking::where('reference_number', $request->booking_reference_number)->first();

        $updateToDate = $request->date ? Carbon::parse($request->date)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null;

        if (!$booking) {
            return response()->json(['error' => 'BOOKING_NOT_FOUND', 'message' => 'Booking not found!'], 400);
        }

        $updateBooking = Booking::where('reference_number', $request->booking_reference_number)
                ->update([
                    'auto_cancel_at' => $updateToDate
                ]);

        // Log action here

        if (!$updateBooking) {
            return response()->json(['error' => 'AUTO_CANCEL_DATE_FAILED_TO_UPDATE', 'message' => 'Failed to update auto-cancel date!'], 400);
        }

        // Create log
        ActivityLog::create([
            'booking_reference_number' => $request->booking_reference_number,

            'action' => 'update_auto_cancel_date',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has updated the auto-cancel date.',
            'model' => 'App\Models\Booking\Booking',
            'model_id' => $booking->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        return $booking;
    }
}
