<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;
use App\Models\Booking\ActivityLog;

class GetLogs extends Controller
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

        return ActivityLog::where('booking_reference_number', $request->booking_reference_number)
                        ->with('causer')
                        ->orderBy('created_at', 'desc')
                        ->get();

    }
}
