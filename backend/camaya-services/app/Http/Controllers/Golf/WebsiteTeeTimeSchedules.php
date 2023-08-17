<?php

namespace App\Http\Controllers\Golf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Golf\TeeTimeSchedule;

class WebsiteTeeTimeSchedules extends Controller
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

        $list = TeeTimeSchedule::whereIn('date', $request->dates)
                            ->where('entity', 'BPO')
                            ->where('status', 'approved')
                            ->select('id', 'allocation', 'entity', \DB::raw("DATE_FORMAT(date, '%Y-%m-%d') as date"), 'mode_of_transportation', 'time')
                            ->withCount(['guests' => function ($query) {                                
                                $query->join('bookings', 'tee_time_guest_schedules.booking_reference_number', '=', 'bookings.reference_number');
                                $query->whereIn('bookings.status', ['pending', 'confirmed']);
                            }])->get();

        // return collect($list)->groupBy('date');
        return $list;
    }
}
