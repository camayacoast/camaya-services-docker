<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Carbon\Carbon;
use App\Models\Booking\DailyGuestLimit;
use App\Models\Booking\DailyGuestLimitNote;

class GetDailyGuestPerDayMonthYear extends Controller
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

        $start_of_month = Carbon::parse($request->month.' '.$request->year)->startOfMonth()->format('Y-m-d');
        $end_of_month = Carbon::parse($request->month.' '.$request->year)->endOfMonth()->format('Y-m-d');

        /**
         * Get notes
         */
    
        $notes = DailyGuestLimitNote::where('date', '>=', $start_of_month)
                                    ->where('date', '<=', $end_of_month)
                                    ->get();

        $guest_limits = DailyGuestLimit::where('date', '>=', $start_of_month)
                                    ->where('date', '<=', $end_of_month)
                                    ->get();

        return response()->json([ 'guest_limits' => $guest_limits, 'notes' => $notes], 200);
                                
    }
}
