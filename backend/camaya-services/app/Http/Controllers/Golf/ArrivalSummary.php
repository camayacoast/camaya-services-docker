<?php

namespace App\Http\Controllers\Golf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;

use Carbon\Carbon;

class ArrivalSummary extends Controller
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

        $selected_date = $request->date ?? Carbon::now()->setTimezone('Asia/Manila');

        return Guest::whereHas('booking', function ($query) use ($selected_date) {
            $query->whereDate('start_datetime', '<=', $selected_date)
                ->whereDate('start_datetime', '>=', $selected_date);

            $query->whereIn('status', ['confirmed', 'pending']);
            $query->where( function ($q) {
                $q->where('reference_number', 'like', 'GD-%')
                    ->orWhere('reference_number', 'like', 'GO-%');
            });
        })
        ->with(['booking' => function ($q) {
            $q->select('id', 'reference_number','type','status', 'customer_id', 'start_datetime', 'mode_of_transportation');
            $q->with('guestVehicles');
            $q->with('customer');
            $q->with('tags');
        }])
        ->with('tee_time.schedule')
        ->with(['active_trips' => function ($q) {
            $q->join('schedules', 'schedules.trip_number', '=', 'trips.trip_number');
            $q->join('routes', 'routes.id', '=', 'schedules.route_id');
            $q->join('locations as destination', 'destination.id', '=', 'routes.destination_id');
            $q->select('trips.guest_reference_number', 'trips.trip_number', 'trips.status','destination.code as destination_code');
        }])
        // ->with('commercialEntry:code,tap_datetime')
        ->whereNull('deleted_at')
        ->get();
    }
}
