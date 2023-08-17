<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetCorregidorBookingsPerDate extends Controller
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

        $corregidor_bookings_refno = \App\Models\Booking\Addon::with(['booking' => function ($q) use ($request) {
            // $q->where('start_datetime', '>=', $request->start_date);
            // $q->where('start_datetime', '<=', $request->end_date);
        }])
        ->where('date', '>=', $request->start_date)
        ->where('date', '<=', $request->end_date)
        ->pluck('booking_reference_number');

        $bookings = \App\Models\Booking\Booking::whereIn('reference_number', $corregidor_bookings_refno)->get();

        return $bookings;
    }
}
