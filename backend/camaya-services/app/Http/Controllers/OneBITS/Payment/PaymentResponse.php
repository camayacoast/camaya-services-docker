<?php

namespace App\Http\Controllers\OneBITS\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Passenger;
use App\Models\Transportation\Trip;
use App\Models\Transportation\SeatSegment;
use App\Models\Transportation\Seat;
use App\Mail\OneBITS\NewBooking;
use App\Mail\OneBITS\FailedBooking;
use Illuminate\Support\Facades\Mail;

use Carbon\Carbon;

class PaymentResponse extends Controller
{

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        $response = $request->all();

        Log::channel('onebitspayment')->info($response);

        // return redirect('https://its.1bataan.com.test/book/payment/response?err='.$response['ErrDesc'])->with($response);

        if ($response['Status'] == 1) {
            // update order to PAID

            $payment_channel = "credit_card";

            switch ($response['PaymentId']) {
                case '1':
                    $payment_channel = "credit_card";
                    break;
            }

            /**
             * Make adjustments to trip tickets
             */
            $tickets = Ticket::where('group_reference_number', $response['RefNo'])
                                ->with('passenger')
                                ->with('schedule.route.origin')
                                ->with('schedule.route.destination')
                                ->with('schedule.transportation')
                                ->with('trip')
                                ->get();

            Ticket::where('group_reference_number', $response['RefNo'])->update([
                'paid_at' => Carbon::now(),
                'payment_reference_number' => $response['TransId'],
                'mode_of_payment' => 'online_payment',
                'payment_status' => 'paid',
                'payment_provider' => 'ipay88',
                'payment_channel' => $payment_channel,
                'payment_provider_reference_number' => $response['TransId'],
                'status' => 'paid',
            ]);

            /**
             * Check in trip status
             */

            $pluck_ticket_ref_no = collect($tickets)->pluck('reference_number')->all();

            Trip::whereIn('ticket_reference_number', $pluck_ticket_ref_no)->update([
                'status' => 'checked_in',
                'checked_in_at' => Carbon::now(),
            ]);

            /**
             * Mail passenger with Boarding passes
             */
            $passenger_email = $tickets[0]['email'];

            Mail::to($passenger_email)
                // ->cc($additional_emails)
                ->send(new NewBooking($response['RefNo'], $tickets));
            // Mail::to($request->email)
            //     // ->cc($additional_emails)
            //     ->send(new NewBooking());


            return redirect(env('ONEBITS_URL').'/book/payment/response?success=1&ref='.$response['RefNo'])->with($response);
        } else {
            // update order to FAIL
            $tickets = Ticket::where('group_reference_number', $response['RefNo'])
                                // ->with('passenger')
                                // ->with('schedule.route.origin')
                                // ->with('schedule.route.destination')
                                // ->with('schedule.transportation')
                                // ->with('trip')
                                ->get();

            $pluck_ticket_ref_no = collect($tickets)->pluck('reference_number')->all();

            Ticket::where('group_reference_number', $response['RefNo'])->update([
                'payment_status' => 'payment_failed',
                'mode_of_payment' => 'online_payment',
                'payment_provider' => 'ipay88',
                'status' => 'voided',
                'voided_at' => Carbon::now(),
            ]);

            Trip::whereIn('ticket_reference_number', $pluck_ticket_ref_no)->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
            ]);


            /**
             * Seat segment decrement
             */
            if ($pluck_ticket_ref_no) {
                $trip_seat_segment_ids = Trip::whereIn('ticket_reference_number', $pluck_ticket_ref_no)->distinct('seat_segment_id')->pluck('seat_segment_id');

                SeatSegment::whereIn('id', $trip_seat_segment_ids)
                    ->decrement('used', count($pluck_ticket_ref_no) / 2);
            }

            $passenger_email = $tickets[0]['email'];

            Mail::to($passenger_email)
            // ->cc($additional_emails)
            ->send(new FailedBooking());
            // ->send(new NewBooking($response['RefNo'], $tickets));

            return redirect(env('ONEBITS_URL').'/book/payment/response?err='.$response['ErrDesc'])->with($response);
        }
    }
}
