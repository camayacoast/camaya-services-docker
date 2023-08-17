<?php

namespace App\Http\Controllers\OneBITS\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Trip;

use App\Mail\OneBITS\NewBooking;
use App\Mail\OneBITS\FailedBooking;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ConfirmBooking extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // Update Ticket records per group_reference_number

        Ticket::where('group_reference_number', $request['group_reference_number'])->update([
            'paid_at' => Carbon::now(),
            'payment_reference_number' => $request->or_number,
            'mode_of_payment' => $request->mode_of_payment,
            'payment_status' => 'paid',
            'payment_provider' => 'cashier',
            // 'payment_channel' => null,
            // 'payment_provider_reference_number' => null,
            'remarks' => $request->remarks,
            'ticket_type' => $request->ticket_type,
            'status' => 'paid',
        ]);

        /**
         * Check in trip status
         */

        $tickets = Ticket::where('group_reference_number', $request->group_reference_number)->get();

        $pluck_ticket_ref_no = collect($tickets)->pluck('reference_number')->all();

        Trip::whereIn('ticket_reference_number', $pluck_ticket_ref_no)->update([
            'status' => 'checked_in',
            'checked_in_at' => Carbon::now(),
        ]);

        // Send email
        /**
         * Mail passenger with Boarding passes
         */
        $passenger_email = $tickets[0]['email'];


        
        Mail::to($passenger_email)
            // ->cc($additional_emails)
            ->send(new NewBooking($request->group_reference_number, $tickets));


        return 'OK';
    }
}
