<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;
use App\Models\Booking\ActivityLog;

class UpdateBillingInstructions extends Controller
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
        $booking = Booking::where('reference_number', $request->booking_reference_number)->first();

        if (!$booking) {
            return response()->json(['error' => 'ERROR'], 400);
        }

        // Create log
        // use App\Models\Booking\ActivityLog;
        ActivityLog::create([
            'booking_reference_number' => $booking->reference_number,
            'action' => 'update_billing_instructions',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has updated the booking billing instructions to "'.$request->value.'".',
            'model' => 'App\Models\Booking\Booking',
            'model_id' => $booking->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        $booking->update([
            'billing_instructions' => $request->value,
        ]);

        return $booking->refresh();
    }
}
