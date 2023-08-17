<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MyBookings extends Controller
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
        $my_bookings = \App\Models\Booking\Booking::where('bookings.created_by', $request->user()->id)
                // ->join('customers', 'customers.id', '=', 'bookings.customer_id')
                ->with('customer')
                ->with('bookingOf:id,object_id,first_name,last_name,user_type,email')
                ->with('bookedBy:id,object_id,first_name,last_name,user_type,email')
                // ->select('bookings.*', 'customers.first_name', 'customers.last_name', 'customers.email', 'customers.contact_number')
                // ->orderBy('start_datetime', 'ASC')
                ->orderBy('status', 'ASC')
                ->orderBy('created_at', 'DESC');

        if (isset($request->status_filters)) {
            // $my_bookings->whereIn('status', explode(',',$request->status_filters));
        }

        return $my_bookings->get();
    }
}
