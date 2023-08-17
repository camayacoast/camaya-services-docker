<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking\Guest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

use App\Models\AutoGate\Pass;
use App\Models\AutoGate\Tap;

use App\Models\Booking\Invoice;

class DttRevenueReport extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $start_date = $request->start_date ?? Carbon::now()->setTimezone('Asia/Manila');
        $end_date = $request->end_date ?? Carbon::now()->setTimezone('Asia/Manila');

        $dtt_arriving_guests = Guest::whereHas('booking', function ($query) use ($start_date, $end_date) {
                $query->whereDate('start_datetime', '<=', $end_date)
                    ->whereDate('start_datetime', '>=', $start_date);
                
                $query->whereIn('type', ['DT']);

                $query->whereIn('status', ['confirmed', 'pending']);
            })
            ->whereDoesntHave('booking.tags', function ($q) {
                $q->where('name', 'Ferry Only');
            })
            ->with(['booking' => function ($q) {
                $q->with('guestVehicles');
                $q->with('customer');
                $q->with('tags');
                $q->with('invoices');
                $q->with('booking_payments');
                $q->select('id', 'reference_number','type','status', 'customer_id', 'start_datetime', 'mode_of_transportation', 'mode_of_payment');
            }])
            ->with('guestTags')
            ->with(['active_trips' => function ($q) {
                $q->join('schedules', 'schedules.trip_number', '=', 'trips.trip_number');
                $q->join('routes', 'routes.id', '=', 'schedules.route_id');
                $q->join('locations as destination', 'destination.id', '=', 'routes.destination_id');
                $q->select('trips.guest_reference_number', 'trips.trip_number', 'trips.status','destination.code as destination_code');
            }])
            // ->with('commercialEntry:code,tap_datetime')
            ->whereNull('deleted_at')
            ->get();

        ////////

        return [
            'dtt_arriving_guests' => $dtt_arriving_guests,
        ];
    }
}
