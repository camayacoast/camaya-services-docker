<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Booking\CancelBookingRequest;

use App\Models\Booking\Booking;
use App\Models\Booking\ActivityLog;
use App\Models\Booking\Addon;
use App\Models\Booking\LandAllocation;
use App\Models\Booking\Guest;

use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomAllocation;

use App\Models\Transportation\Trip;
use App\Models\Transportation\SeatSegment;

use App\Models\AutoGate\Pass;

use App\Mail\Booking\CancelBooking as CancelBookingMail;
use Illuminate\Support\Facades\Mail;

use Carbon\Carbon;

class CancelBooking extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CancelBookingRequest $request)
    {
        //
        // return $request->all();
        $booking = Booking::where('reference_number', $request->reference_number)->first();

        $isBookingDraft = $booking->status == 'draft' ? true : false;

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
            'cancelled_by' => $request->user()->id
        ]);

        /**
         * Cancel all room reservation if the booking has room reservations
         */
        $room_reservations = RoomReservation::where('booking_reference_number', $booking->reference_number)->get();

        if ($room_reservations) {

            /**
             * Update room allocation used column
             */
            $room_reservations_allocation_used = RoomReservation::where('booking_reference_number', $booking->reference_number)
                                ->whereNotIn('status', ['cancelled', 'voided', 'transferred'])
                                ->pluck('allocation_used');

            foreach ($room_reservations_allocation_used as $value) {
                RoomAllocation::whereIn('id', $value)
                        ->decrement('used');
            }

            RoomReservation::where('booking_reference_number', $booking->reference_number)
            ->whereNotIn('status', ['cancelled', 'voided', 'transferred'])
            ->update([
                'status' => 'cancelled'
            ]);
             

        }


        /**
         * Cancel all ferry trips (Not yet implemented)
         */

        // Set trip record to cancel
        $trips = Trip::where('booking_reference_number', $booking->reference_number)->whereNotIn('status', ['no_show', 'cancelled'])->get();

        Trip::where('booking_reference_number', $booking->reference_number)
                ->update([
                    'cancelled_at' => Carbon::now(),
                    'status' => 'cancelled',
                ]);

        // Return allocation to segment
        if (count($trips)) {
            foreach ($trips as $trip) {
                $guest = Guest::where('reference_number', $trip['guest_reference_number'])->first();

                if ($guest->type != 'infant') {
                    SeatSegment::where('id', $trip['seat_segment_id'])
                                ->decrement('used');
                }
            }
        }

        // Customer arrival status
        Guest::where('booking_reference_number', $booking->reference_number)
            ->update([
                'status' => 'booking_cancelled'
            ]);

        // Void all passes
        Pass::where('booking_reference_number', $booking->reference_number)
            ->update([
                'status' => 'voided'
            ]);

        /**
         * Cancel all addons inventory
         */
        $addons = Addon::where('booking_reference_number', $booking->reference_number)
                    ->whereNotIn('status', ['cancelled', 'voided'])
                    ->get();

        if ($addons) {
            
            /**
             * Update addon
             */

            Addon::where('booking_reference_number', $booking->reference_number)
            ->whereNotIn('status', ['cancelled', 'voided'])
            ->update([
                'status' => 'cancelled'
            ]);
             
        }

        // SEND EMAIL AFTER
        $booking = Booking::where('reference_number', $request->reference_number)
                        ->with('bookedBy')
                        ->with('customer')
                        ->with(['guests' => function ($q) {
                            $q->with('guestTags');
                            $q->with('tripBookings.schedule.transportation');
                            $q->with('tripBookings.schedule.route.origin');
                            $q->with('tripBookings.schedule.route.destination');
                        }])
                        ->with('inclusions.packageInclusions')
                        ->with('inclusions.guestInclusion')
                        ->with('invoices')
                        ->withCount(['invoices as invoices_grand_total' => function ($q) {
                            $q->select(\DB::raw('sum(grand_total)'));
                        }])
                        ->withCount(['invoices as invoices_balance' => function ($q) {
                            $q->select(\DB::raw('sum(balance)'));
                        }])
                        ->first();

        if ($booking->mode_of_transportation == 'own_vehicle') {
            $booking->load('guestVehicles');
        }

        $camaya_transportations = [];

        if ($booking->mode_of_transportation == 'camaya_transportation') {
            $booking->load('camaya_transportation');

            $camaya_transportations = \App\Models\Transportation\Schedule::whereIn('trip_number', collect($booking['camaya_transportation'])->unique('trip_number')->pluck('trip_number')->all())
                                ->with('transportation')
                                ->with('route.origin')
                                ->with('route.destination')
                                ->get();

        } else {
            if ($booking->sales_director_id) {
                LandAllocation::where('date', $booking->start_datetime)->where('owner_id', $booking->sales_director_id)
                            ->where('entity', 'RE')
                            ->where('status', 'approved')
                            ->decrement('used', $booking->adult_pax + $booking->kid_pax);
            }
        }


        $additional_emails = [];

        if (isset($booking->additionalEmails)) {
            $additional_emails = collect($booking->additionalEmails)->pluck('email')->all();
        }

        if (!$isBookingDraft) {

            Mail::to($booking->customer->email)
                                ->cc($additional_emails)
                                ->send(new CancelBookingMail($booking, $camaya_transportations));
                                
        }

        // Create log
        ActivityLog::create([
            'booking_reference_number' => $request->reference_number,

            'action' => 'cancel_booking',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has cancelled the booking.',
            'model' => 'App\Models\Booking\Booking',
            'model_id' => $booking->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        return $booking;

    }
}
