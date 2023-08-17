<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Booking\ConfirmBookingRequest;

use App\Models\Booking\Booking;
use App\Models\Booking\ActivityLog;
use App\Models\Booking\Addon;
use App\Models\Booking\LandAllocation;
use App\Models\Booking\Guest;

use App\Models\Hotel\RoomReservation;

use App\Models\AutoGate\Pass;

use App\Models\Transportation\Trip;

use App\Mail\Booking\BookingConfirmation;
use Illuminate\Support\Facades\Mail;

use Carbon\Carbon;

class ConfirmBooking extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ConfirmBookingRequest $request)
    {
        //
        // return $request->all();
        $booking = Booking::where('reference_number', $request->reference_number)->first();

        $booking->update([
            'status' => 'confirmed',
            'approved_at' => Carbon::now(),
            'approved_by' => $request->user()->id
        ]);

        $booking = Booking::where('reference_number', $request->reference_number)
                ->with('bookedBy')
                ->with('customer')
                ->with(['guests' => function ($q) {
                    $q->with('tee_time.schedule');
                    $q->with('guestTags');
                    $q->with(['tripBookings.schedule' => function ($q) {
                        // $q->orderBy('trip_date', 'asc');
                        // $q->orderBy('start_time', 'asc');
                        // $q->orderBy(DB::raw("DATE_FORMAT(STR_TO_DATE(CONCAT(trip_date, ' ', start_time), '%d-%m-%Y %H:%i:%s'),'%d-%m-%Y %H:%i:%s')"), 'asc');
                        $q->with('transportation');
                        $q->with('route.origin');
                        $q->with('route.destination');
                        // $q->select('*', "start_time as custom_time");
                        
                    }]);
                    
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
        }

        $additional_emails = [];

        if (isset($booking->additionalEmails)) {
            $additional_emails = collect($booking->additionalEmails)->pluck('email')->all();
        }

        Mail::to($booking->customer->email)
                            ->cc($additional_emails)
                            ->send(new BookingConfirmation($booking, $camaya_transportations));


        /**
         * Confirm all room reservation if the booking has room reservations
         */
        $room_reservations = RoomReservation::where('booking_reference_number', $booking->reference_number)->get();

        if ($room_reservations) {
            RoomReservation::where('booking_reference_number', $booking->reference_number)
            ->whereNotIn('status', ['cancelled', 'voided', 'transferred'])
            ->update([
                'status' => 'confirmed'
            ]);
        }

        /**
         * Confirm all ferry trips (Not yet implemented)
         */
        /**
         * Confirm all trips
         */
        $trips = Trip::where('booking_reference_number', $booking->reference_number)->get();

        Trip::where('booking_reference_number', $booking->reference_number)
        ->where('status', 'pending')
        // ->whereNotIn('status', ['boarded', 'no_show'])
        ->update([
            'status' => 'checked_in'
        ]);

        // if (count($trips)) {
        //     foreach ($trips as $trip) {
        //         SeatSegment::where('id', $trip['seat_segment_id'])
        //                     ->increment('used');
        //     }
        // }

        // Customer arrival status
        Guest::where('booking_reference_number', $booking->reference_number)
            ->update([
                'status' => 'arriving'
            ]);

        // Revert all passes
        Pass::where('booking_reference_number', $booking->reference_number)
            ->update([
                'status' => 'created'
            ]);

        $addons = Addon::where('booking_reference_number', $booking->reference_number)
        ->whereNotIn('status', ['cancelled', 'voided'])
        ->get();

        if ($addons) {
            
            /**
             * Update addon
             */

            Addon::where('booking_reference_number', $booking->reference_number)
            ->whereIn('status', ['cancelled', 'voided'])
            ->update([
                'status' => 'confirmed'
            ]);
            
        }

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

        if ($booking->mode_of_transportation != 'camaya_transportation') {
            if ($booking->sales_director_id) {
                LandAllocation::where('date', $booking->start_datetime)->where('owner_id', $booking->sales_director_id)
                    ->where('entity', 'RE')
                    ->where('status', 'approved')
                    ->increment('used', $booking->adult_pax + $booking->kid_pax);
            }
        }

        // Create log
        ActivityLog::create([
            'booking_reference_number' => $booking->reference_number,

            'action' => 'confirm_booking',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has confirmed the booking.',
            'model' => 'App\Models\Booking\Booking',
            'model_id' => $booking->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        return $booking;
    }
}
