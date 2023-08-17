<?php

namespace App\Http\Controllers\OneBITS\Payment\PaymentResponse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\OneBITS\Ticket;
use Carbon\Carbon;
use PayMaya\PayMayaSDK;
use App\Models\Transportation\Trip;
use Illuminate\Support\Facades\Mail;
use App\Mail\OneBITS\NewBooking;
use Illuminate\Support\Facades\DB;
use PayMaya\Core\CheckoutAPIManager;

class PaymayaSuccess extends Controller
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



        /**
         * change trip ticket status to paid
         */

        $tickets = Ticket::where('group_reference_number', $response['reference_number'])
            ->with('passenger')
            ->with('schedule.route.origin')
            ->with('schedule.route.destination')
            ->with('schedule.transportation')
            ->with('trip')
            ->get();


        $checkoutId = $tickets[0]['payment_reference_number'];
        if ($tickets && $checkoutId !== '') {
            $APIManager = new CheckoutAPIManager();
            $responses = $APIManager->retrieveCheckout($checkoutId);
            $responseArr = json_decode($responses, true);

            if (!isset($responseArr['paymentStatus'])) {
                echo json_encode($responseArr);
                return false;
            }
            if ($responseArr['paymentStatus'] == 'PAYMENT_SUCCESS') {
                Ticket::where('group_reference_number', $response['reference_number'])->update([
                    'paid_at' => Carbon::now(),
                    'payment_status' => 'paid',
                    'payment_provider_reference_number' => $responseArr['transactionReferenceNumber'],
                    'payment_provider' => 'maya',
                    'payment_channel' => $responseArr['paymentScheme'],
                    'status' => 'paid',
                ]);
                $pluck_ticket_ref_no = collect($tickets)->pluck('reference_number')->all();

                Trip::whereIn('ticket_reference_number', $pluck_ticket_ref_no)->update([
                    'status' => 'checked_in',
                    'checked_in_at' => Carbon::now(),
                ]);

                $passenger_email = $tickets[0]['email'];
                Mail::to($passenger_email)
                    ->send(new NewBooking($response['reference_number'], $tickets));
                return redirect(env('ONEBITS_URL') . '/book/payment/response?success=1&ref=' . $response['reference_number'])->with($response);
            }
            //error
            return redirect(env('ONEBITS_URL') . '/book/payment/response?err=' . 'Paymaya Error')->with($response);
        }
        //error
        return redirect(env('ONEBITS_URL') . '/book/payment/response?err=' . 'Paymaya Error')->with($response);
    }
}
