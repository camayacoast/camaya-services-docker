<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;
use App\Models\Booking\ActivityLog;

class UpdateRemarks extends Controller
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


        $bookingRemarksToEdit = Booking::where('reference_number', $request->booking_reference_number)->first();

        if (!$bookingRemarksToEdit) {
            return response()->json(['error' => 'ERROR'], 400);
        }

        // Create log
        // use App\Models\Booking\ActivityLog;
        ActivityLog::create([
            'booking_reference_number' => $bookingRemarksToEdit->reference_number,
            'action' => 'update_remarks',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has updated the booking remarks.',
            'model' => 'App\Models\Booking\Booking',
            'model_id' => $bookingRemarksToEdit->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        $bookingRemarksToEdit->update([
            'remarks' => $request->value,
        ]);

        return $bookingRemarksToEdit->refresh();
    }
}
