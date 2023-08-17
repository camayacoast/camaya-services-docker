<?php

namespace App\Http\Controllers\OneBITS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\OneBITS\Ticket;

use DB;
use Carbon\Carbon;

class GetBookings extends Controller
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

        $date = Carbon::parse($request->date)->format('Y-m-d');

        $bookings = Ticket::join('schedules', 'schedules.trip_number', '=', 'tickets.trip_number')
                        ->leftJoin('routes', 'schedules.route_id', '=', 'routes.id')
                        ->leftJoin('locations as origin', 'routes.origin_id', '=', 'origin.id')
                        ->leftJoin('locations as destination', 'routes.destination_id', '=', 'destination.id')
                        ->where('schedules.trip_date', $date)
                        ->select(DB::raw('count(tickets.reference_number) as ticket_count'), 
                            'schedules.trip_date', 
                            'group_reference_number', 
                            'tickets.trip_number', 
                            'tickets.status as ticket_status', 
                            'payment_status', 
                            DB::raw('sum(tickets.amount) as total_amount'), 
                            DB::raw('sum(tickets.discount) as total_discount'),
                            'origin.code as origin_code',
                            'destination.code as destination_code',
                        )
                        ->groupBy(
                            'tickets.group_reference_number', 
                            'tickets.trip_number', 
                            'schedules.trip_date', 
                            'ticket_status', 
                            'payment_status',
                            'origin_code',
                            'destination_code'
                        )
                        ->get();

        $array = [];

        foreach ($bookings->groupBy('group_reference_number')->all() as $key => $item) {
            $array[] = [
                'booking_reference_number' => $key,
                'payment_status' => $item[0]['payment_status'],
                'ticket_status' => $item[0]['ticket_status'],
                'trip_date' => $item[0]['trip_date'],
                'total_booking_amount' => collect($item)->sum('total_amount') - collect($item)->sum('total_discount'),
                'total_ticket_count' => collect($item)->sum('ticket_count'),
                'trips' => $item
            ];
        }

        return $array;

    }
}
