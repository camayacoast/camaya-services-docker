<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;

class SearchBookings extends Controller
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

        if (isset($request->booking_reference_number)) {
            $booking = Booking::where('reference_number', $request->booking_reference_number)->first();

            return response()->json([
                'reference_number' => $booking->reference_number,
                'status' => $booking->status,
            ], 200);

        } else {
            $bookings = Booking::where( function ($q) use ($request) {

                            // booking_creation_date /
                            // booking_source /
                            // booking_status /
                            // booking_type /
                            // customer /
                            // date_of_arrival /
                            // date_of_departure /
                            // mode_of_transportation /
                            // user_type /

                            if ($request->customer)                 $q->where('customer_id', $request->customer);
                            if ($request->user_type)                $q->where('user_type', $request->user_type);
                            if ($request->booking_creation_date)    $q->whereDate('bookings.created_at', date('Y-m-d',strtotime($request->booking_creation_date)));
                            if ($request->date_of_arrival)          $q->whereDate('start_datetime', date('Y-m-d',strtotime($request->date_of_arrival)));
                            if ($request->date_of_departure)        $q->whereDate('end_datetime', date('Y-m-d',strtotime($request->date_of_departure)));
                            if ($request->booking_status)           $q->where('status', $request->booking_status);
                            if ($request->booking_type)             $q->where('type', $request->booking_type);
                            if ($request->mode_of_transportation)   $q->where('mode_of_transportation', $request->mode_of_transportation);
                            if ($request->booking_source)           $q->whereIn('source', [$request->booking_source]);
                            if ($request->portal)                   $q->whereIn('portal', [$request->portal]);
                        })
                        ->join('customers', 'customers.id', '=', 'bookings.customer_id')
                        ->select('bookings.*', 'customers.first_name', 'customers.last_name', 'customers.email', 'customers.contact_number')
                        ->get();

            return $bookings;
        }

        

    }
}
