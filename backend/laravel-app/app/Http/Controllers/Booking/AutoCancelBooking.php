<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;
use App\Models\Booking\Guest;
use App\Models\Booking\GuestVehicle;
use App\Models\Booking\ActivityLog;
use App\Models\Booking\Addon;

use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomAllocation;

use App\Models\Transportation\Trip;
use App\Models\Transportation\SeatSegment;

use App\Models\AutoGate\Pass;

use App\Mail\Booking\AutoCancelBooking as AutoCancelBookingMail;
use Illuminate\Support\Facades\Mail;

use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

class AutoCancelBooking extends Controller
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

        /**
         * Checks if booking is already for cancellation
         */

        $bookingsToCancel = Booking::whereIn('status', ['pending'])
                ->whereNotNull('auto_cancel_at')
                ->where('auto_cancel_at', '<=', Carbon::now()->setTimezone('Asia/Manila'))
                ->select('reference_number')
                ->get();

        if ($bookingsToCancel) {

            $bookingsToCancelReferenceNumbers = collect($bookingsToCancel)->pluck('reference_number')->all();

            // Execute cancel
            Booking::whereIn('status', ['pending'])
                    ->whereNotNull('auto_cancel_at')
                    ->where('auto_cancel_at', '<=', Carbon::now()->setTimezone('Asia/Manila'))
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => Carbon::now()->setTimezone('Asia/Manila'),
                        'reason_for_cancellation' => 'Auto-cancelled by the system'
                    ]);

            /**
             * Cancel all room reservation if the booking has room reservations
             */
            $room_reservations = RoomReservation::whereIn('booking_reference_number', $bookingsToCancelReferenceNumbers)->get();

            if ($room_reservations) {
                
                /**
                 * Update room allocation used column
                 */
                $room_reservations_allocation_used = RoomReservation::whereIn('booking_reference_number', $bookingsToCancelReferenceNumbers)
                                    ->whereNotIn('status', ['cancelled', 'voided', 'transferred'])
                                    ->pluck('allocation_used');

                foreach ($room_reservations_allocation_used as $value) {
                    RoomAllocation::whereIn('id', $value)
                            // ->decrement('used');
                            ->update(['used' => \DB::raw('IF(used <= 0, 0, used - 1)')]);

                }

                RoomReservation::whereIn('booking_reference_number', $bookingsToCancelReferenceNumbers)
                ->whereNotIn('status', ['cancelled', 'voided', 'transferred'])
                ->update([
                    'status' => 'cancelled'
                ]);

                

            }


            /**
             * Cancel all ferry trips (Not yet implemented)
             */

            // Set trip record to cancel
            $trips = Trip::whereIn('booking_reference_number', $bookingsToCancelReferenceNumbers)->whereNotIn('status', ['no_show', 'cancelled'])->get();

            Trip::whereIn('booking_reference_number', $bookingsToCancelReferenceNumbers)
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
                                // ->decrement('used');
                                ->update(['used' => \DB::raw('IF(used <= 0, 0, used - 1)')]);
                    }

                }
            }

            // Void all passes
            Pass::whereIn('booking_reference_number', $bookingsToCancelReferenceNumbers)
                ->update([
                    'status' => 'voided'
                ]);

            /**
             * Cancel all addons inventory
             */
            
            $addons = Addon::whereIn('booking_reference_number', $bookingsToCancelReferenceNumbers)
                        ->whereNotIn('status', ['cancelled', 'voided'])
                        ->get();

            if ($addons) {
                
                /**
                 * Update addon
                 */

                Addon::whereIn('booking_reference_number', $bookingsToCancelReferenceNumbers)
                ->whereNotIn('status', ['cancelled', 'voided'])
                ->update([
                    'status' => 'cancelled'
                ]);
                
            }



            // SEND EMAIL AFTER
            $bookings = Booking::whereIn('reference_number', $bookingsToCancelReferenceNumbers)
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
                            ->with('additionalEmails')
                            ->get();

            foreach ($bookings as $booking) {

                // Create log
                ActivityLog::create([
                    'booking_reference_number' => $booking->reference_number,

                    'action' => 'auto_cancel',
                    'description' => 'System has auto cancelled the booking.',
                    'model' => 'App\Models\Booking\Booking',
                    'model_id' => $booking->id,
                    'properties' => null,

                    'created_by' => null,
                ]);

                $isBookingDraft = $booking['status'] == 'draft' ? true : false;

                if ($booking['mode_of_transportation'] == 'own_vehicle') {
                    $booking['guestVehicles'] = GuestVehicle::where('booking_reference_number', $booking['reference_number'])->get();
                }

                $camaya_transportations = [];

                if ($booking['mode_of_transportation'] == 'camaya_transportation') {
                    $booking['camaya_transportation'] = Trip::where('booking_reference_number', $booking['reference_number'])->get();

                    $camaya_transportations = \App\Models\Transportation\Schedule::whereIn('trip_number', collect($booking['camaya_transportation'])->unique('trip_number')->pluck('trip_number')->all())
                                        ->with('transportation')
                                        ->with('route.origin')
                                        ->with('route.destination')
                                        ->get();

                }

                $additional_emails = [];

                if (isset($booking['additionalEmails'])) {
                    $additional_emails = collect($booking['additionalEmails'])->pluck('email')->all();
                }

                if (!$isBookingDraft) {

                    Mail::to($booking['customer']['email'])
                                        ->cc($additional_emails)
                                        ->send(new AutoCancelBookingMail($booking, $camaya_transportations));
                                        
                }
            }

            // Logs cancelled bookings
            Log::info('Auto-cancelled bookings: '. implode(', ', $bookingsToCancelReferenceNumbers));
        }
        
        return $bookingsToCancelReferenceNumbers;

    }
}
