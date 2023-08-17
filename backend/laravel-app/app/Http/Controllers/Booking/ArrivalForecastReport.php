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
use App\Models\RealEstate\SalesTeam;

class ArrivalForecastReport extends Controller
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

        $arriving_guests = Guest
            // ::whereHas('booking', function ($query) use ($start_date, $end_date) {
            //     $query->whereDate('start_datetime', '<=', $end_date)
            //         ->whereDate('start_datetime', '>=', $start_date);

            //     $query->whereIn('status', ['confirmed', 'pending']);

            // })
            ::with(['booking' => function ($q) {
                $q->with('customer.user');
                $q->with(['bookedBy' => function ($q) {
                    $q->parentTeam();
                }]);
                $q->with('sales_director');
                $q->with('guestVehicles');
                $q->with('tags');
                $q->with('notes');
                $q->with('invoices');
                $q->select('id', 'reference_number','type','status', 'customer_id', 'start_datetime', 'end_datetime', 'mode_of_transportation', 'created_by', 'sales_director_id');

            }])
            ->whereDoesntHave('booking.tags', function ($q) {
                $q->where('name', 'Ferry Only');
            })
            ->join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($end_date, $start_date) {
                $q->whereDate('bookings.start_datetime', '<=', $end_date)
                    ->whereDate('bookings.start_datetime', '>=', $start_date);

                $q->whereIn('bookings.status', ['confirmed', 'pending']);
            })
            ->whereIn('guests.status', ['arriving', 'on_premise', 'checked_in'])
            ->with('guestTags')
            ->with(['active_trips' => function ($q) {
                $q->join('schedules', 'schedules.trip_number', '=', 'trips.trip_number');
                $q->join('routes', 'routes.id', '=', 'schedules.route_id');
                $q->join('locations as destination', 'destination.id', '=', 'routes.destination_id');
                $q->select('trips.guest_reference_number', 'trips.trip_number', 'trips.status','destination.code as destination_code');
            }])
            // ->with('commercialEntry:code,tap_datetime')
            ->whereNull('deleted_at')
            ->select('guests.*')
            ->get();

        return [
            'arriving_guests' => $arriving_guests
        ];
    }
}
