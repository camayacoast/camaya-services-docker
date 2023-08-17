<?php

namespace App\Http\Controllers\Golf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Golf\TeeTimeSchedule;

class TeeTimeScheduleList extends Controller
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
        return TeeTimeSchedule::withCount(['guests' => function ($query) {                                
                                $query->join('bookings', 'tee_time_guest_schedules.booking_reference_number', '=', 'bookings.reference_number');
                                $query->whereIn('bookings.status', ['pending', 'confirmed']);
                            }])
                            // ->where('status', 'approved')
                            ->get();
    }
}
