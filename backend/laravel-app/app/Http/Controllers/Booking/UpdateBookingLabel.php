<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;
use App\Models\Booking\Booking;
use App\Models\Booking\ActivityLog;

class UpdateBookingLabel extends Controller
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

        // $bookingLabelToEdit = Booking::find($request->id);

        $bookingLabelToEdit = Booking::where('reference_number', $request->booking_reference_number)->first();

        if (!$bookingLabelToEdit) {
            return response()->json(['error' => 'ERROR'], 400);
        }

        // Create log
        // use App\Models\Booking\ActivityLog;
        ActivityLog::create([
            'booking_reference_number' => $bookingLabelToEdit->reference_number,
            'action' => 'update_booking_label',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has updated the booking label from "'.$bookingLabelToEdit['label'].' " to "'.$request->value.' ".',
            'model' => 'App\Models\Booking\Booking',
            'model_id' => $bookingLabelToEdit->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        $bookingLabelToEdit->update([
            'label' => $request->value,
        ]);

        return $bookingLabelToEdit->refresh();
    }
}
