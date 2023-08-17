<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\BookingTag;
use App\Models\Booking\Booking;
use App\Models\Booking\ActivityLog;

class UpdateBookingTags extends Controller
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

        if(!$booking) {
            return response()->json(['error' => 'BOOKING_NOT_FOUND'], 400);
        }

        //
        /**
         * Update additional emails
         */

         // Delete all booking additional emails
         BookingTag::where('booking_id', $booking->id)->delete();


        if ($request->tags) {
            foreach ($request->tags as $tag) {
                BookingTag::create([
                    'booking_id' => $booking->id,
                    'name' => $tag,
                    'created_by' => $request->user()->id,
                ]);
            }
        }

        // Create log
        // use App\Models\Booking\ActivityLog;
        ActivityLog::create([
            'booking_reference_number' => $request->booking_reference_number,

            'action' => 'update_booking_tags',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has updated the booking tags ('.implode(',', $request->tags).').',
            'model' => 'App\Models\Booking\Booking',
            'model_id' => $booking->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        return 'OK';
    }
}
