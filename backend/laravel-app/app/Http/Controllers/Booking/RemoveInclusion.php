<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;
use App\Models\Booking\Guest;
use App\Models\Booking\Inclusion;
use App\Models\Booking\Invoice;
use App\Models\Booking\Addon;
use App\Models\Booking\ActivityLog;

use App\Models\AutoGate\Pass;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomReservation;

use App\Models\Transportation\Trip;
use App\Models\Transportation\SeatSegment;
use Carbon\Carbon;

class RemoveInclusion extends Controller
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

        $inclusion = Inclusion::find($request->id);

        if (!$inclusion) {
            return response()->json(['error' => 'INCLUSION_NOT_FOUND'], 400);
        }

        $inclusion->delete();
        $inclusion->update([
            // 'deleted_at' => Carbon::now(),
            'deleted_by' => $request->user()->id,
        ]);

        // 35. Remove room allocation in the Hotel Calendar for deleted room package on the booking page (Invoice)
        // if (env('APP_ENV') != 'production') {
            if ($inclusion->type == 'room_reservation') {
                $room_reservation = RoomReservation::query()
                    ->where('booking_reference_number', '=', $inclusion->booking_reference_number)
                    ->orderBy('id', 'desc')
                    ->first();
                $room_allocations = RoomAllocation::find($room_reservation->allocation_used);
                foreach($room_allocations as $room_allocation) {
                    $room_allocation->decrement('used');
                    @$room_reservation->delete();
                }
            }
        // }
        // end

        /**
         * Remove addon inventory
         */
        if ($inclusion->type == 'package') {
            $package_inclusions = Inclusion::where('parent_id', $inclusion->id)
                                                ->where('type', 'package_inclusion')
                                                ->whereNull('deleted_at')
                                                ->get();

                foreach ($package_inclusions as $package_inclusion) {
                    /**
                     * Cancel all addons inventory
                     */
                    $addon = Addon::where('booking_reference_number', $package_inclusion['booking_reference_number'])
                                    ->where('guest_reference_number', $package_inclusion['guest_reference_number'])
                                    ->where('code', $package_inclusion['code'])
                                    ->whereNotIn('status', ['cancelled', 'voided'])
                                    ->first();
                    if ($addon) {

                        /**
                         * Update addon
                         */
        
                        Addon::where('booking_reference_number', $package_inclusion['booking_reference_number'])
                            ->where('guest_reference_number', $package_inclusion['guest_reference_number'])
                            ->where('code', $package_inclusion['code'])
                            ->whereNotIn('status', ['cancelled', 'voided'])
                            ->update([
                                'status' => 'voided'
                            ]);
        
                        // Void all passes for corregidor
                        Pass::where('booking_reference_number', $package_inclusion['booking_reference_number'])
                                ->where('guest_reference_number', $package_inclusion['guest_reference_number'])
                                ->where('inclusion_id', $package_inclusion['id'])
                                ->update([
                                    'status' => 'voided'
                                ]);
                        
                    }
                }
        } else {
            /**
             * Cancel all addons inventory
             */
            $addon = Addon::where('booking_reference_number', $inclusion->booking_reference_number)
                        ->where('guest_reference_number', $inclusion->guest_reference_number)
                        ->where('code', $inclusion->code)
                        ->whereNotIn('status', ['cancelled', 'voided'])
                        ->first();

            if ($addon) {
                
                /**
                 * Update addon
                 */

                Addon::where('booking_reference_number', $inclusion->booking_reference_number)
                    ->where('guest_reference_number', $inclusion->guest_reference_number)
                    ->where('code', $inclusion->code)
                    ->whereNotIn('status', ['cancelled', 'voided'])
                    ->update([
                        'status' => 'voided'
                    ]);

                // Void all passes for corregidor
                Pass::where('booking_reference_number', $inclusion->booking_reference_number)
                        ->where('guest_reference_number', $inclusion->guest_reference_number)
                        ->where('inclusion_id', $inclusion->id)
                        ->update([
                            'status' => 'voided'
                        ]);
                
            }

            // Remove Ticket & Pass

            if ($inclusion->type == 'ticket') {

                // Get trip #
                $trip_number = explode("_", $inclusion->code)[0];

                // Remove guest camaya transportation, if there's any
                Trip::where('guest_reference_number', $inclusion->guest_reference_number)
                ->where('trip_number', $trip_number)
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => Carbon::now()
                ]);

                // Return the allocation to seat segment
                $seat_segment_ids = Trip::where('guest_reference_number', $inclusion->guest_reference_number)
                                    ->where('trip_number', $trip_number)
                                    ->pluck('seat_segment_id');

                $guest = Guest::where('reference_number', $inclusion->guest_reference_number)->first();
                
                if ($seat_segment_ids && $guest->type != 'infant') {
                    SeatSegment::whereIn('id', $seat_segment_ids)
                        ->decrement('used');
                }

                // Remove passes
                Pass::where('inclusion_id', $inclusion->id)
                ->update([
                    'status' => 'voided',
                    'deleted_by' => $request->user()->id,
                    'deleted_at' => Carbon::now(),
                ]);

                // Check if booking still has ticket
                $inclusion_tickets = Inclusion::where('booking_reference_number', $inclusion->booking_reference_number)
                                                ->where('type', 'ticket')
                                                ->whereNull('deleted_at')
                                                ->count();

                $booking = Booking::where('reference_number', $inclusion->booking_reference_number)->first();
                
                if ($booking->mode_of_transportation == 'camaya_transportation' && $inclusion_tickets == 0) {
                    // Change mode of transpo if all the tickets are deleted in the inclusions
                    Booking::where('reference_number', $inclusion->booking_reference_number)
                            ->update(['mode_of_transportation' => 'own_vehicle']);

                    ActivityLog::create([
                        'booking_reference_number' => $inclusion->booking_reference_number,
            
                        'action' => 'change_mode_of_transpo',
                        'description' => $request->user()->first_name.' '.$request->user()->last_name.' has changed the mode of transpo to undecided. All Ferry tickets deleted in the booking inclusions.',
                        'model' => 'App\Models\Booking\Booking',
                        'model_id' => $booking->id,
                        'properties' => null,
            
                        'created_by' => $request->user()->id,
                    ]);
                }

            }
        }

        // Remove passes meal stub
        /**
         * Not yet implemented. Passes should be removed manually for now.
         * We should record the inclusion_id on passes before we can implement this.
         */
        // Pass::where('guest_reference_number', $inclusion->guest_reference_number)
        //         ->where('inclusion_id', $inclusion->id)
        //         ->update([
        //             'status' => 'voided',
        //             'deleted_by' => $request->user()->id,
        //             'deleted_at' => Carbon::now(),
        //         ]);

        // Update invoice
        $invoice = Invoice::where('id', $inclusion->invoice_id)->first();

        $inclusions = Inclusion::where('invoice_id', $invoice->id)
                                    ->whereNull('deleted_at')
                                    ->get();

        $total_inclusions_cost = 0;
        foreach ($inclusions as $i) {
            $total_inclusions_cost = $total_inclusions_cost + (($i['price'] * $i['quantity']) - $i['discount']);
        }

        $grand_total = ($total_inclusions_cost - $invoice->discount);
        $balance = ($total_inclusions_cost - $invoice->discount) - $invoice->total_payment;

        $invoice->update([
            'total_cost' => $total_inclusions_cost,
            'grand_total' => $grand_total < 0 ? 0 : $grand_total,
            'balance' => $balance < 0 ? 0 : $balance,
        ]);

        // Activity log

        ActivityLog::create([
            'booking_reference_number' => $inclusion->booking_reference_number,

            'action' => 'remove_inclusion',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has deleted inclusion '. $inclusion->code .' ('. $inclusion->id .').',
            'model' => 'App\Models\Booking\Inclusion',
            'model_id' => $inclusion->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        return $inclusion;
    }
}
