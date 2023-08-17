<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Trip;

class PassengerList extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $date = $request->date;
        //
        return Trip::with(['schedule' => function ($q) use ($date) {
                        $q->where('trip_date', $date);
                        $q->with('transportation');
                        $q->with('route.origin');
                        $q->with('route.destination');
                    }])
                    ->with(['passenger' => function ($q) {
                        $q->orderBy('last_name');
                    }])
                    ->with(['booking' => function ($q) use ($date) {
                        $q->with('customer');
                        $q->with('tags');
                    }])
                    ->get();
    }
}
