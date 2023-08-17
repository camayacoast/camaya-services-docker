<?php

namespace App\Http\Controllers\Concierge;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;
use Carbon\Carbon;

class Dashboard extends Controller
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
        $selected_date = $request->date ?? Carbon::now()->setTimezone('Asia/Manila');
        //
        /**
         * Arriving guests today
         */

        $arriving_guests = Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($selected_date) {
                $q->whereDate('bookings.start_datetime', '<=', $selected_date)
                    ->whereDate('bookings.start_datetime', '>=', $selected_date);

                $q->whereIn('bookings.status', ['confirmed', 'pending', 'cancelled']);
                $q->whereDoesntHave('booking.tags', function ($q) {
                    $q->where('name', 'Ferry Only');
                });
            })
            ->with(['booking' => function ($q) {
                $q->with('guestVehicles:booking_reference_number,model,plate_number');
                $q->with(['customer' => function ($q) {
                    $q->select('address', 'contact_number', 'email', 'first_name', 'id', 'last_name', 'middle_name', 'nationality', 'object_id');
                    $q->with('user:object_id,first_name,last_name,email,user_type');
                }]);
                $q->with(['bookedBy' => function ($q) {
                    $q->selectRaw('id,email,first_name,middle_name,last_name,object_id,user_type');
                    $q->parentTeam();
                }]);
                $q->with('sales_director:id,first_name,last_name');
                $q->with('tags:booking_id,name');
                $q->with('notes:booking_reference_number,message');
                // $q->with('booking_payments');
                $q->select('id', 'reference_number','type','status', 'customer_id', 'start_datetime', 'end_datetime', 'mode_of_transportation', 'mode_of_payment', 'created_by', 'sales_director_id');
            }])
            ->with('guestTags:name,guest_reference_number')
            ->with(['active_trips' => function ($q) {
                $q->join('schedules', 'schedules.trip_number', '=', 'trips.trip_number');
                $q->join('routes', 'routes.id', '=', 'schedules.route_id');
                $q->join('locations as destination', 'destination.id', '=', 'routes.destination_id');
                $q->select('trips.guest_reference_number', 'trips.trip_number', 'trips.status','destination.code as destination_code');
            }])
            // ->with('commercialEntry:code,tap_datetime')
            ->whereNull('guests.deleted_at')
            ->select('guests.reference_number', 'guests.booking_reference_number', 'guests.first_name', 'guests.last_name', 'guests.nationality', 'guests.related_id', 'guests.status', 'guests.type', 'guests.age')
            ->get()
            ->toArray();

        //-- Active guests
        $guest_status_active = \App\Models\Booking\Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($selected_date) {
                $q->where('bookings.start_datetime', '<=', $selected_date)
                    ->where('bookings.start_datetime', '>=', $selected_date);

                $q->whereIn('bookings.status', ['confirmed', 'pending']);
                $q->whereDoesntHave('booking.tags', function ($q) {
                    $q->where('name', 'Ferry Only');
                });
            })
            ->whereIn('guests.status', ['arriving', 'on_premise', 'checked_in'])
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total, guests.status') // fix
            // ->select('*') // issue
            ->groupBy('guests.status')
            ->get(); 
        
        $guest_status_noshow_cancelled = \App\Models\Booking\Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($selected_date) {
                $q->where('bookings.start_datetime', '<=', $selected_date)
                    ->where('bookings.start_datetime', '>=', $selected_date);

                $q->whereIn('bookings.status', ['cancelled', 'pending', 'confirmed']);
                $q->whereDoesntHave('booking.tags', function ($q) {
                    $q->where('name', 'Ferry Only');
                });
            })
            ->whereIn('guests.status', ['no_show', 'booking_cancelled'])
            ->selectRaw('COUNT(*) as total, guests.status')
            ->groupBy('guests.status')
            ->get();

        return [
            'arriving_guests' => $arriving_guests,

            'guest_status_active' => collect($guest_status_active)->sum('total'),
            'guest_status_arriving' => collect($guest_status_active)->firstWhere('status', 'arriving')['total'] ?? 0,
            'guest_status_onpremise' => collect($guest_status_active)->firstWhere('status', 'on_premise')['total'] ?? 0,
            'guest_status_checkedin' => collect($guest_status_active)->firstWhere('status', 'checked_in')['total'] ?? 0,
            'guest_status_noshow' => collect($guest_status_noshow_cancelled)->firstWhere('status', 'no_show')['total'] ?? 0,
            'guest_status_cancelled' => collect($guest_status_noshow_cancelled)->firstWhere('status', 'booking_cancelled')['total'] ?? 0
        ];
    }
}
