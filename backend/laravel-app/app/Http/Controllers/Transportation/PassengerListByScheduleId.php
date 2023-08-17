<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Trip;

class PassengerListByScheduleId extends Controller
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

        return Trip::where('trip_number', $request->trip_number)
                    ->with(['schedule' => function ($q) {
                        // $q->where('trip_date', $date);
                        $q->with('transportation');
                        $q->with('route.origin');
                        $q->with('route.destination');
                    }])
                    ->with(['passenger' => function ($q) {
                        $q->with('guest_tags');
                        $q->orderBy('last_name');
                    }])
                    ->with(['booking' => function ($q) {
                        $q->with('customer');
                        $q->with('tags');
                    }])
                    ->where('ticket_reference_number', '1')
                    ->get();


    }
}
