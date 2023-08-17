<?php

namespace App\Http\Controllers\OneBITS\Payment\PaymentResponse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PayMaya\PayMayaSDK;
use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Trip;
use PayMaya\Core\CheckoutAPIManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\OneBITS\FailedBooking;
use App\Models\Booking\Guest;
use App\Models\Transportation\SeatSegment;
use Illuminate\Support\Facades\DB;

class PaymayaFailed extends Controller
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
        Log::channel("onebitspayment")->info($response);
        PayMayaSDK::getInstance()->initCheckout(env('MAYA_PUBLIC_API_KEY'), env('MAYA_SECRET_API_KEY'), env('MAYA_API_ENDPOINT_ENV'));

        $tickets = Ticket::where('group_reference_number', $response['reference_number'])
            ->with('passenger')
            ->with('schedule.route.origin')
            ->with('schedule.route.destination')
            ->with('schedule.transportation')
            ->with('trip')
            ->get();

        $checkoutId = $tickets[0]['payment_reference_number'];

        if ($checkoutId === '') {
            echo json_encode([
                'error' => '404',
                'message' => 'Checkout id is missing'
            ]);
            return redirect(env('ONEBITS_URL') . '/book/payment/response?err=' . 'Checkout id is missing')->with($response);
        }
        if ($tickets && $checkoutId !== '') {
            $APIManager = new CheckoutAPIManager();
            $responses = $APIManager->retrieveCheckout($checkoutId);
            $responseArr = json_decode($responses, true);
            $error_code = $responseArr['paymentDetails']['responses']['efs']['unhandledError'][0]['code'] ?? '9999';
            $paymaya_response_message = '(' . $this->paymayaErrorMessage($error_code) . ')';
            if ($responseArr['paymentStatus'] === 'PAYMENT_FAILED') {
                DB::transaction(function () use ($response, $responseArr,$tickets) {
                    Ticket::where('group_reference_number', $response['reference_number'])->update([
                        'paid_at' => Carbon::now(),
                        'payment_status' => 'voided',
                        'payment_provider_reference_number' => $responseArr['transactionReferenceNumber'],
                        'payment_provider' => 'maya',
                        'payment_channel' => $responseArr['paymentScheme'],
                        'status' => 'voided',
                    ]);

                    $pluck_ticket_ref_no = collect($tickets)->pluck('reference_number')->all();


                    $trip = Trip::whereIn('ticket_reference_number', $pluck_ticket_ref_no)->where("status", "pending")->get();
                    $pluck_seat_segment_id = collect($trip)->pluck('seat_segment_id')->all();
                    Trip::whereIn('ticket_reference_number', $pluck_ticket_ref_no)->update([
                        'status' => 'cancelled',
                        'checked_in_at' => Carbon::now(),
                    ]);
                    SeatSegment::whereIn("id", $pluck_seat_segment_id)->decrement('used');
                });
            }
            $passenger_email = $tickets[0]['email'];
            Mail::to($passenger_email)
                // ->cc($additional_emails)
                ->send(new FailedBooking());
            return redirect(env('ONEBITS_URL') . '/book/payment/response?err=' . $paymaya_response_message)->with($response);
        }

        echo json_encode([
            'error' => '404',
            'message' => 'Tickets with ref.# ' . $response['reference_number'] . ' is not found in our system.'
        ]);

        return redirect(env('ONEBITS_URL') . '/book/payment/response?err=' . 'Tickets with ref.# ' . $response['reference_number'] . ' is not found in our system.')->with($response);
    }

    public function paymayaErrorMessage($code)
    {

        $default_message = $code . ' - ' . 'A problem is encountered. Please contact your system administrator.';
        $messages = [
            '2051' => 'Payment Failed due to Insufficient balance',
            '2043' => 'Stolen Card',
            '2059' => 'Issuer Suspected Fraud',
            '2054'=>'Expired card',
            'ACQ070'=>'Key is already expired',
            'ACQ084' => 'Transaction was blocked by the Acquirer',
            'ACQ047' => 'MPI Enrollment/Authentication Verification Error',
            '9999' => 'Transaction is already expired! Please book again' ,
            'ACQ505'=>'Issuer Decline Limit Exceeded - Please retry this card after 24 hours or retry the transaction using a different card.',
            'ACQ508'=>'Please validate your card number and expiry date, then retry the transaction.',
            'ACQ504'=>'Issuer instructed to stop authorization requests for this card. Please retry the transaction using a different card.',
            'ACQ506'=>'Card networks prohibit further authorization requests for this card. Please retry the transaction using a different card.',
            'ACQ507'=>'Please validate your card expiry date and retry the transaction.',
            'ACQ084'=>'Transaction was blocked by the Acquirer',
            '2089'=>'Card Security Code/Card Verification Value is incorrect'
            // If code is not exists in the response
        ];

        $message = (isset($messages[$code])) ? $messages[$code] : $default_message;

        return $message;
    }
}
