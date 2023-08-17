<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingList extends Controller
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

        $bookings = \App\Models\Booking\Booking::whereNotIn('bookings.status', ['draft'])
        ->with('customer:object_id,id,first_name,last_name,nationality,contact_number,address,email')
        ->with('bookingOf:id,first_name,last_name,user_type,email')
        ->with('bookedBy:id,first_name,last_name,user_type,email');
        // ->join('customers', 'customers.id', '=', 'bookings.customer_id')
        // ->select('bookings.*', 'customers.first_name', 'customers.last_name', 'customers.email', 'customers.contact_number')

        if (isset($request->status_filters)) {
            $bookings->whereIn('status', explode(',',$request->status_filters));
        }

        switch ($request->type) {
            case 'active':
                $bookings->whereDate('start_datetime', '<=', Carbon::now()->setTimezone('Asia/Manila'))
                        ->whereDate('end_datetime', '>=', Carbon::now()->setTimezone('Asia/Manila'))
                        ->orderBy('start_datetime', 'ASC');
            break;

            case 'upcoming':
                $bookings->whereDate('start_datetime', '>', Carbon::now()->setTimezone('Asia/Manila'))
                        ->orderBy('start_datetime', 'ASC');
            break;

            case 'past':
                $bookings->whereDate('start_datetime', '<', Carbon::now()->setTimezone('Asia/Manila'))
                        ->whereDate('end_datetime', '<', Carbon::now()->setTimezone('Asia/Manila'))
                        ->orderBy('start_datetime', 'desc')
                        ->limit(300);
            break;
        }

        return $bookings->get();
    }
}
