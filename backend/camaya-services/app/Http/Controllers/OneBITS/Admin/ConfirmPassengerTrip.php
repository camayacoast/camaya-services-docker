<?php

namespace App\Http\Controllers\OneBITS\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Trip;

use Carbon\Carbon;

class ConfirmPassengerTrip extends Controller
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
         * Make adjustments to trip tickets
         */
        // $tickets = Ticket::where('reference_number', $request['reference_number'])->first();

        return "Disabled";

        Ticket::where('reference_number', $request['reference_number'])->update([
            'paid_at' => Carbon::now(),
            // 'payment_reference_number' => $response['TransId'],
            'mode_of_payment' => $request->mode_of_payment,
            'payment_status' => 'paid',
            'payment_provider' => 'cashier',
            // 'payment_channel' => null,
            // 'payment_provider_reference_number' => null,
            'remarks' => $request->remarks,
            'status' => 'paid',
        ]);

        /**
         * Check in trip status
         */

        // $pluck_ticket_ref_no = collect($tickets)->pluck('reference_number')->all();

        Trip::where('ticket_reference_number', $request->reference_number)->update([
            'status' => 'checked_in',
            'checked_in_at' => Carbon::now(),
        ]);

        return 'OK';

    }
}
