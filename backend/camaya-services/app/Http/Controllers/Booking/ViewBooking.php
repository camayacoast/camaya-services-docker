<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;
use App\Models\Booking\Invoice;
use App\Models\Booking\ActivityLog;

class ViewBooking extends Controller
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
        $booking = Booking::where('reference_number', $request->refno)
                            ->with('agent')
                            ->with('sales_director')
                            ->with('bookedBy')
                            ->with('customer')
                            ->with('tags')
                            ->with('notes.author_details')
                            ->with('additionalEmails')
                            ->with('guestVehicles')
                            ->with(['adultGuests' => function ($q) {
                                $q->with('guestInclusions');
                                $q->with('passes.trip');
                                $q->with('guestTags');
                                $q->with('tee_time.schedule');
                                $q->with(['tripBookings' => function ($q) {
                                    $q->whereNotIn('trips.status', ['cancelled', 'no_show']);
                                    $q->join('schedules', 'schedules.trip_number', '=', 'trips.trip_number');
                                    $q->join('routes', 'routes.id', '=', 'schedules.route_id');
                                    $q->join('locations as destination', 'destination.id', '=', 'routes.destination_id');
                                    $q->select('trips.*', 'destination.code as destination_code');
                                }]);
                            }])
                            ->with(['kidGuests' => function ($q) {
                                $q->with('guestInclusions');
                                $q->with('passes.trip');
                                $q->with('guestTags');
                                $q->with('tee_time.schedule');
                                $q->with(['tripBookings' => function ($q) {
                                    $q->whereNotIn('trips.status', ['cancelled', 'no_show']);
                                    $q->join('schedules', 'schedules.trip_number', '=', 'trips.trip_number');
                                    $q->join('routes', 'routes.id', '=', 'schedules.route_id');
                                    $q->join('locations as destination', 'destination.id', '=', 'routes.destination_id');
                                    $q->select('trips.*', 'destination.code as destination_code');
                                }]);
                            }])
                            ->with(['infantGuests' => function ($q) {
                                $q->with('guestInclusions');
                                $q->with('passes.trip');
                                $q->with('guestTags');
                                $q->with(['tripBookings' => function ($q) {
                                    $q->whereNotIn('trips.status', ['cancelled', 'no_show']);
                                    $q->join('schedules', 'schedules.trip_number', '=', 'trips.trip_number');
                                    $q->join('routes', 'routes.id', '=', 'schedules.route_id');
                                    $q->join('locations as destination', 'destination.id', '=', 'routes.destination_id');
                                    $q->select('trips.*', 'destination.code as destination_code');
                                }]);
                            }])
                            // ->with('kidGuests.guestInclusions')
                            // ->with('infantGuests.guestInclusions')
                            ->with(['inclusions' => function ($query) {
                                $query->with('guestInclusion');
                                $query->with('packageInclusions.guestInclusion');
                                // $query->with('packageInclusions.product');
                                // $query->with('package');
                                // $query->with('product');
                                $query->with('deleted_by_user');
                                $query->withTrashed();
                            }])
                            ->with('attachments.uploader')
                            ->addSelect(['*',
                                'balance' => Invoice::select(\DB::raw('sum(balance) as total_balance'))
                                                    ->whereColumn('booking_reference_number', 'bookings.reference_number')
                                                    ->limit(1)
                            ])
                            ->with(['invoices' => function ($query) {
                                $query->with(['inclusions' => function ($query) {
                                    $query->with('guestInclusion');
                                    $query->with('packageInclusions.guestInclusion');
                                    $query->with('deleted_by_user');
                                    $query->withTrashed();
                                }]);
                                $query->with('payments');
                            }])
                            ->with(['room_reservations_no_filter' => function ($query) {
                                $query->with('room');
                                $query->with('room_type.images');
                                $query->whereNotIn('status', ['cancelled', 'blackout', 'voided', 'transferred']);
                            }])
                            ->with('cancelled_by')
                            ->withCount('activity_logs')
                            ->first();

        // Create log
        ActivityLog::create([
            'booking_reference_number' => $request->refno,

            'action' => 'view_booking',
            'description' => $request->refno.' has been viewed by '.$request->user()->first_name.' '.$request->user()->last_name,
            'model' => 'App\Models\Booking\Booking',
            'model_id' => $booking->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        return $booking;
    }
}
