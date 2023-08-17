<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;
use App\Models\Booking\Booking;
use App\Models\Booking\Inclusion;
use App\Models\Booking\Invoice;
use App\Models\Booking\ActivityLog;
use App\Models\Booking\Addon;
use App\Models\Booking\LandAllocation;

use App\Models\Transportation\Trip;
use App\Models\Transportation\SeatSegment;

use App\Models\AutoGate\Pass;

use Carbon\Carbon;

class DeleteGuest extends Controller
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

        $guest = Guest::find($request->guest_id);

        if (!$guest) {
            return response()->json(['error' => 'GUEST_NOT_FOUND'], 400);
        }

        // Update guest deleted at and deleted by

        $guest->update([
            'deleted_at' => Carbon::now(),
            'deleted_by' => $request->user()->id
        ]);


        $inclusion_tickets = Inclusion::where('guest_reference_number', $guest->reference_number)->where('type', 'ticket')->count();

        if ($inclusion_tickets) {
            // Remove guest camaya transportation, if there's any
            Trip::where('guest_reference_number', $guest->reference_number)
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => Carbon::now()
                ]);
            // Return the allocation to seat segment
            $seat_segment_ids = Trip::where('guest_reference_number', $guest->reference_number)->pluck('seat_segment_id');
            
            if ($seat_segment_ids && $guest->type != 'infant') {
                SeatSegment::whereIn('id', $seat_segment_ids)
                    ->decrement('used');
            }
        }


        // Decrement booking pax
        Booking::where('reference_number', $guest->booking_reference_number)
                    ->decrement($guest->type.'_pax');

        $inclusion_invoice_ids = Inclusion::where('guest_reference_number', $guest->reference_number)->pluck('invoice_id');

        // Remove guest inclusions
        Inclusion::where('guest_reference_number', $guest->reference_number)
            ->update([
                'deleted_by' => $request->user()->id,
                'deleted_at' => Carbon::now(),
            ]);

        /**
         * Cancel all addons inventory
         */
        $addon = Addon::where('guest_reference_number', $guest->reference_number)
                    ->whereNotIn('status', ['cancelled', 'voided'])
                    ->first();

        if ($addon) {
            
            /**
             * Update addon
             */

            Addon::where('guest_reference_number', $guest->reference_number)
            ->whereNotIn('status', ['cancelled', 'voided'])
            ->update([
                'status' => 'cancelled'
            ]);
             
        }

        // Remove passes
        Pass::where('guest_reference_number', $guest->reference_number)
                ->update([
                    'status' => 'voided',
                    'deleted_by' => $request->user()->id,
                    'deleted_at' => Carbon::now(),
                ]);

        // Land Allocation Decrement
        $booking = Booking::where('reference_number', $guest->booking_reference_number)->first();
        if ($booking->sales_director_id && $guest->type != 'infant') {
            LandAllocation::where('date', $booking->start_datetime)->where('owner_id', $booking->sales_director_id)
                        ->where('entity', 'RE')
                        ->where('status', 'approved')
                        ->update(['used' => \DB::raw('IF(used <= 0, 0, used - 1)')]);
                        // ->decrement('used', 1);
        }
        

        // Update invoice
        $invoices = Invoice::whereIn('id', $inclusion_invoice_ids)->get();

        foreach ($invoices as $invoice) {

            $inclusions = Inclusion::where('invoice_id', $invoice['id'])
                                        ->whereNull('deleted_at')
                                        ->get();

            $total_inclusions_cost = 0;
            foreach ($inclusions as $i) {
                $total_inclusions_cost = $total_inclusions_cost + (($i['price'] * $i['quantity']) - $i['discount']);
            }

            $grand_total = ($total_inclusions_cost - $invoice['discount']);
            $balance = ($total_inclusions_cost - $invoice['discount']) - $invoice['total_payment'];

            $invoice->update([
                'total_cost' => $total_inclusions_cost,
                'grand_total' => $grand_total < 0 ? 0 : $grand_total,
                'balance' => $balance < 0 ? 0 : $balance,
            ]);

        }

        // Logs the deletion
        // Create log
        // use App\Models\Booking\ActivityLog;
        ActivityLog::create([
            'booking_reference_number' => $guest->booking_reference_number,

            'action' => 'update_guest',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has deleted guest '.$guest['first_name'].' '.$guest['last_name'].' ('.$guest['age'].').',
            'model' => 'App\Models\Booking\Guest',
            'model_id' => $guest->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        return $guest;
    }
}
