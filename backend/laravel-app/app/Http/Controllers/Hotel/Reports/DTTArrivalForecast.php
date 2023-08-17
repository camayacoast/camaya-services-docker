<?php

namespace App\Http\Controllers\Hotel\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class DTTArrivalForecast extends Controller
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
        // DB::enableQueryLog();

        $bookings = \App\Models\Booking\Booking::whereNotIn('bookings.status', ['draft'])
            ->with('customer')
            ->with(['inclusions' => function($query){
                $query->where('type', '=', 'room_reservation');
            }])
            ->orderBy('start_datetime', 'ASC');

        return $bookings->get();
        
        // $bookings->get();

        // return dd(DB::getQueryLog());
    }
}
