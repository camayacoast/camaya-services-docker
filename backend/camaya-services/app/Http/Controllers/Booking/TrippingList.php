<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\User;

class TrippingList extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        $agent_ids = User::where('user_type', 'agent')->pluck('id');

        $bookings = \App\Models\Booking\Booking::whereNotIn('bookings.status', ['draft'])
        // ->join('customers', 'customers.id', '=', 'bookings.customer_id')
        // ->select('bookings.*', 'customers.first_name', 'customers.last_name', 'customers.email', 'customers.contact_number')
        ->with('customer')
        ->whereIn('created_by', $agent_ids)
        ->with('bookingOf:id,first_name,last_name,user_type,email')
        ->with('bookedBy:id,first_name,last_name,user_type,email')
        ->orderBy('start_datetime', 'ASC');

        if (isset($request->status_filters)) {
            $bookings->whereIn('status', explode(',',$request->status_filters));
        }

        switch ($request->type) {
            case 'active':
                $bookings->whereDate('start_datetime', '<=', Carbon::now()->setTimezone('Asia/Manila'))
                        ->whereDate('end_datetime', '>=', Carbon::now()->setTimezone('Asia/Manila'));
            break;

            case 'upcoming':
                $bookings->whereDate('start_datetime', '>', Carbon::now()->setTimezone('Asia/Manila'));
            break;

            case 'past':
                $bookings->whereDate('start_datetime', '<', Carbon::now()->setTimezone('Asia/Manila'))
                        ->whereDate('end_datetime', '<', Carbon::now()->setTimezone('Asia/Manila'));
            break;
        }

        return $bookings->get();
    }
}
