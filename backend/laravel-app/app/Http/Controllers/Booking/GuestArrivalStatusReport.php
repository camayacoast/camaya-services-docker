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

class GuestArrivalStatusReport extends Controller
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

        $arriving_guests = Guest::whereHas('booking', function ($query) use ($start_date, $end_date) {
                $query->whereDate('start_datetime', '<=', $end_date)
                    ->whereDate('start_datetime', '>=', $start_date);

                    $query->whereIn('status', ['confirmed', 'pending', 'cancelled']);
            })
            ->whereDoesntHave('booking.tags', function ($q) {
                $q->where('name', 'Ferry Only');
            })
            ->with(['booking' => function ($q) {
                $q->with('guestVehicles');
                $q->with('customer');
                $q->with('tags');
                $q->with('invoices');
                $q->select('id', 'reference_number','type','status', 'customer_id', 'start_datetime', 'end_datetime', 'mode_of_transportation');
            }])
            ->whereIn('status', ['arriving', 'on_premise', 'checked_in', 'no_show', 'booking_cancelled'])
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

        $guest_status_active = Guest::whereHas('booking', function ($query) use ($start_date, $end_date) {
                $query->whereDate('start_datetime', '<=', $end_date)
                    ->whereDate('start_datetime', '>=', $start_date);

                $query->whereIn('status', ['confirmed', 'pending']);
            })
            ->whereDoesntHave('booking.tags', function ($q) {
                $q->where('name', 'Ferry Only');
            })
            ->whereIn('status', ['arriving', 'on_premise', 'checked_in'])
            ->whereNull('deleted_at')
            ->get();

        $guest_status_arriving = Guest::whereHas('booking', function ($query) use ($start_date, $end_date) {
                $query->whereDate('start_datetime', '<=', $end_date)
                    ->whereDate('start_datetime', '>=', $start_date);

                $query->whereIn('status', ['confirmed', 'pending']);
            })
            ->whereDoesntHave('booking.tags', function ($q) {
                $q->where('name', 'Ferry Only');
            })
            ->whereIn('status', ['arriving'])
            ->whereNull('deleted_at')
            ->get();

        $guest_status_onpremise = Guest::whereHas('booking', function ($query) use ($start_date, $end_date) {
                $query->whereDate('start_datetime', '<=', $end_date)
                    ->whereDate('start_datetime', '>=', $start_date);

                $query->whereIn('status', ['confirmed', 'pending']);
            })
            ->whereDoesntHave('booking.tags', function ($q) {
                $q->where('name', 'Ferry Only');
            })
            ->whereIn('status', ['on_premise'])
            ->whereNull('deleted_at')
            ->get();

        $guest_status_checkedin = Guest::whereHas('booking', function ($query) use ($start_date, $end_date) {
                $query->whereDate('start_datetime', '<=', $end_date)
                    ->whereDate('start_datetime', '>=', $start_date);

                $query->whereIn('status', ['confirmed', 'pending']);
            })
            ->whereDoesntHave('booking.tags', function ($q) {
                $q->where('name', 'Ferry Only');
            })
            ->whereIn('status', ['checked_in'])
            ->whereNull('deleted_at')
            ->get();

        $guest_status_noshow = Guest::whereHas('booking', function ($query) use ($start_date, $end_date) {
                $query->whereDate('start_datetime', '<=', $end_date)
                    ->whereDate('start_datetime', '>=', $start_date);

                $query->whereIn('status', ['confirmed', 'pending']);
            })
            ->whereDoesntHave('booking.tags', function ($q) {
                $q->where('name', 'Ferry Only');
            })
            ->whereIn('status', ['no_show'])
            ->get();

        $guest_status_cancelled = Guest::whereHas('booking', function ($query) use ($start_date, $end_date) {
                $query->whereDate('start_datetime', '<=', $end_date)
                    ->whereDate('start_datetime', '>=', $start_date);

                $query->whereIn('status', ['cancelled']);
            })
            ->whereDoesntHave('booking.tags', function ($q) {
                $q->where('name', 'Ferry Only');
            })
            ->whereIn('status', ['booking_cancelled'])
            ->get();

        return [
            'arriving_guests' => $arriving_guests,
            'guest_status_active' => $guest_status_active,
            'guest_status_arriving' => $guest_status_arriving,
            'guest_status_onpremise' => $guest_status_onpremise,
            'guest_status_checkedin' => $guest_status_checkedin,
            'guest_status_noshow' => $guest_status_noshow,
            'guest_status_cancelled' => $guest_status_cancelled,
        ];
    }
}
