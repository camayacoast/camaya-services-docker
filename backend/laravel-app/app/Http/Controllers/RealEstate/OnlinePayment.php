<?php

namespace App\Http\Controllers\RealEstate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Str;
use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\RealEstatePayment;
use App\Models\RealEstate\RealEstatePaymentStatus;
use App\Models\RealEstate\RealestatePaymentActivityLog;

use App\Http\Requests\RealEstate\OnlinePaymentRequest;
use App\Mail\RealEstate\NewPayment;

use App\Mail\RealEstate\SuccessfulPaymentPayMaya;
use App\Mail\GolfPaymentTransaction as GolfPaymentTransactionMail;

use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

use Illuminate\Support\Facades\Http;

use PayMaya\PayMayaSDK;
use PayMaya\API\Webhook;
use PayMaya\API\Checkout;
use PayMaya\Model\Checkout\Item;
use PayMaya\Model\Checkout\ItemAmount;
use PayMaya\Model\Checkout\ItemAmountDetails;

// Golf
use App\PaymentTransaction;
use App\User;
use App\ViberSubscriber;
use App\ClientProfile;

class OnlinePayment extends Controller
{
    //

    public function __construct()
    {

    }

    public function makePayment(OnlinePaymentRequest $request)
    {

        // return $request->all();

        if (env('PAYMENT_GATEWAY_OPEN') == false) {
            return 'All Payment Gateways are closed.';
        }

        if (!$request->gateway) {
            return 'No gateway selected';
        }

        switch ($request->gateway) {
            case 'DragonPay':
                if (env('PAYMENT_GATEWAY_DRAGONPAY_OPEN') == false) {
                    return response()->json(['message' => 'DragonPay Payment Gateway is closed.'], 200);
                }
                return self::dragonPay($request);
            break;

            case 'PayMaya':
                if (env('PAYMENT_GATEWAY_PAYMAYA_OPEN') == false) {
                    return response()->json(['message' => 'PayMaya Payment Gateway is closed.'], 200);
                }
                return self::payMaya($request);
            break;

            case 'PesoPay':
                if (env('PAYMENT_GATEWAY_PESOPAY_OPEN') == false) {
                    return response()->json(['message' => 'PesoPay Payment Gateway is closed.'], 200);
                }
                return self::pesoPay($request);
            break;

            default:
                return 'Unknown payment gateway.';
        }

    }

    /**
     * PESOPAY
     * */

    private function pesoPay(Request $request)
    {
        $url = env('PESOPAY_PAYMENT_ENDPOINT');
        $merchantId = env('PESOPAY_MERCHANT_ID');
        $secureHashSecret  = env('PESOPAY_SECURE_HASH_SECRET');

        $generateTransactionID = Str::upper(Str::random(10));
        while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
            $generateTransactionID = Str::upper(Str::random(10));
        }

        $secureHash = $this->generatePaymentSecureHash($merchantId, $generateTransactionID, '608', $request->amount, 'N', $secureHashSecret);

        $params = [
            'transaction_id' => $generateTransactionID,
            // 'client_id' => $request->client_id,
            'client_number' => $request->account_number,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email_address,
            'contact_number' => $request->contact_number,
            'sales_agent' => $request->sales,
            'sales_manager' => $request->sales_manager,
            'currency' => 'PHP',
            'payment_amount' => $request->amount,
            'payment_gateway' => $request->gateway,
            'payment_type' => $request->payment_type,
            // 'payment_channel' => $request->payment_channel,
            'payment_encode_type' => 'online_payment',
            // 'payment_gateway_reference_number' => $request->payment_gateway_reference_number,
            'remarks' => $request->remarks,
        ];

        if( in_array($request->payment_type, ['others', 'hoa_fees', 'camaya_air_payment']) ) {
            $params['is_verified'] = 1;
        }

        $newPayment = RealEstatePayment::create($params);

        $newPayment->paymentStatuses()->create([
            'status' => 'PENDING',
            'message' => 'PesoPay pending payment.',
        ]);

        $data = [
            'generateTransactionID' => $generateTransactionID,
            'amount' => $request->amount,
            'secureHash' => $secureHash,
            'pesopay_endpoint' => env('PESOPAY_PAYMENT_ENDPOINT'),
            'merchantId' => env('PESOPAY_MERCHANT_ID'),
            'cancelUrl' => env('REAL_ESTATE_PAYMENT_PORTAL_URL') . '/payment-cancelled?t=' . $generateTransactionID,
            'failUrl' => env('REAL_ESTATE_PAYMENT_PORTAL_URL') . '/payment-failed?transaction_id=' . $generateTransactionID,
            'successUrl' => env('APP_URL').'/pesopay/success/' . $generateTransactionID,

        ];

        return view('payment.pesopay_form', $data);
    }

    public function pesoPaySuccess(Request $request)
    {
        $payment = RealEstatePayment::where('transaction_id', $request->transaction_id)->first();
        $subject = "[SUCCESS] Camaya Online Payment Confirmation | Transaction ID: ". $request->transaction_id;
        $message = 'PesoPay success payment.';

        $response = [
            'result' => 'OK',
            'status' => 'SUCCESS',
            'message' => $message
        ];

        $payment->paymentStatuses()->create([
            'status' => 'SUCCESS',
            'message' => $message,
        ]);

        $payment->update([
            'paid_at' => Carbon::now(),
            'payment_gateway_reference_number' => $request->transaction_id
        ]);

        if ($subject && $payment->email) {
            Mail::to($payment->email)
                ->send(new NewPayment($payment, $subject, $response, $response));
        }

        return redirect()->to(env('REAL_ESTATE_PAYMENT_PORTAL_URL').'/payment-successful?transaction_id='.$request->transaction_id)->send();
    }

    /**
     * Used in Pesopay Merchant Administration Dashboard
     * Profile > Payment Account Settings > Return Value Link (Datafeed)
     *  - URL must inputed in datafeed field
     * URL: https://services.camayacoast.com/pesopay/datafeed
     */
    public function pesoPayDataFeed(Request $request)
    {
        // mandatory: handshake message with pesopay datafeed system
        echo 'OK';

        if( isset($request->Ref) ) {
            $transaction_id = $request->Ref;
            $payment_reference_number = $request->PayRef;
            // 0 = succeeded, 1 = failure, Others = error
            $successCode = $request->successcode;
            $pay_method = $request->payMethod;

            $payment = RealEstatePayment::where('transaction_id', $transaction_id)->first();

            if( $payment ) {
                $payment->update([
                    'paid_at' => Carbon::now(),
                    'payment_gateway_reference_number' => $payment_reference_number,
                    'payment_channel' => $pay_method
                ]);

                // If successCode is != SUCCESS please refer to dashboard for error reason
                switch ($successCode) {
                    case 0:
                        $status = 'SUCCESS';
                        break;
                    case 1:
                        $status = 'FAILLED';
                        break;
                    case 'Others':
                        $status = 'ERROR';
                        break;
                }

                // Updating Status Message Comming from Datafeed
                $payment->paymentStatuses()->where([
                    'transaction_id' => $transaction_id
                ])->update([
                    'status' => $status
                ]);
            }
        }
    }

    public function generatePaymentSecureHash($merchantId, $merchantReferenceNumber, $currencyCode, $amount, $paymentType, $secureHashSecret)
    {
		$buffer = $merchantId . '|' . $merchantReferenceNumber . '|' . $currencyCode . '|' . $amount . '|' . $paymentType . '|' . $secureHashSecret;
		//echo $buffer;
		return sha1($buffer);
	}

    /**
     * DRAGONPAY
     * */

    private function dragonPay(Request $request)
    {
        $url = env('PAYMENT_GATEWAY_DRAGONPAY_URL').'/Pay.aspx?';

        $generateTransactionID = Str::upper(Str::random(10));

        // Creates a new reference number if it encounters duplicate
        while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
            $generateTransactionID = Str::upper(Str::random(10));
        }

        $parameters = array(
            'merchantid' => env('PAYMENT_GATEWAY_DRAGONPAY_ID'),
            'txnid' => $generateTransactionID,
            'amount' => number_format($request->amount, 2, '.', ''),
            'ccy' => 'PHP',
            'description' => 'RE - '.$request->payment_type.' - '. ($request->client_number ? $request->client_number : 'NA') .' - '. $request->first_name . " " . $request->last_name,
            // Add client number in description
            'email' => $request->email_address,
        );

        $parameters['key'] = env('PAYMENT_GATEWAY_DRAGONPAY_KEY');
        $digest_string = implode(':', $parameters);
        unset($parameters['key']);

        $parameters['digest'] = sha1($digest_string);

        $url .= http_build_query($parameters, '', '&');

        $params = [
            'transaction_id' => $generateTransactionID,
            // 'client_id' => $request->client_id,
            'client_number' => $request->account_number,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email_address,
            'contact_number' => $request->contact_number,
            'sales_agent' => $request->sales,
            'sales_manager' => $request->sales_manager,
            'currency' => 'PHP',
            'payment_amount' => $request->amount,
            'payment_gateway' => $request->gateway,
            'payment_type' => $request->payment_type,
            // 'payment_channel' => $request->payment_channel,
            'payment_encode_type' => 'online_payment',
            // 'payment_gateway_reference_number' => $request->payment_gateway_reference_number,
            'remarks' => $request->remarks,
        ];

        if( in_array($request->payment_type, ['others', 'hoa_fees', 'camaya_air_payment']) ) {
            $params['is_verified'] = 1;
        }

        $newPayment = RealEstatePayment::create($params);

        if (!$newPayment) {
            return response()->json(['error' => 'Failed to make payment record. Please try again.'], 400);
        }

        return redirect()->away($url);
    }


    public function paymentReturn(Request $request)
    {

        $page = 'payment-successful';

        if ($request->status != 'S') {
            $page = 'payment-pending';
        }

        $parameters = array_merge($request->all(),
            [
                'transaction_id' => $request->txnid
            ]
        );

        // Display payment success or fail
        $url = env('REAL_ESTATE_PAYMENT_PORTAL_URL').'/'.$page.'?';
        $url .= http_build_query($parameters, '', '&');

        return redirect()->away($url);
        // return $request->all();
    }

    public function paymentPostback(Request $request)
    {

        // return $request->all();
        $subject = null;
        $response = [];

        // return $response;

        $status = null;

        $payment = RealEstatePayment::where('transaction_id', $request->txnid)->first();

        switch ($request->status) {
            case 'S':

                $subject = "[SUCCESS] Camaya Online Payment Confirmation | Transaction ID: ". $request->txnid;

                $response = [
                    'result' => 'OK',
                    'status' => 'SUCCESS',
                    'message' => $request->message
                ];

                $payment->paymentStatuses()->create([
                    'status' => 'SUCCESS',
                    'message' => $request->message,
                ]);

                $payment->update([
                    'paid_at' => Carbon::now(),
                    'payment_gateway_reference_number' => $request->refno
                ]);

            break;

            case 'P':

                $status = 'PENDING';

            break;
            case 'F':

                $status = 'FAILURE';

            break;

            case 'U':

                $status = 'UNKNOWN';

            break;

            case 'R':

                $status = 'REFUND';

            break;

            case 'K':

                $status = 'CHARGEBACK';

            break;

            case 'V':

                $status = 'VOID';

            break;

            case 'A':

                $status = 'AUTHORIZED';

            break;
        }


        if (isset($status)) {

            $subject = "[".$status."] Camaya Online Payment Confirmation | Transaction ID: ". $request->txnid;

            $response = [
                'result' => $status,
                'status' => $status,
                'message' => $request->message
            ];

            $payment->paymentStatuses()->create([
                'status' => $status,
                'message' => $request->message,
            ]);

            $payment->update([
                'payment_gateway_reference_number' => $request->refno
            ]);

        }

        if ($subject && $payment->email) {
            Mail::to($payment->email)
                ->send(new NewPayment($payment, $subject, $response, $request->all()));
        }


        return $response;
    }


    /**
     *  PAYMAYA ONLINE PAYMENTS
     */
    private function payMaya(Request $request)
    {
        // return $request->all();

        $generateTransactionID = Str::upper(Str::random(10));

        // Creates a new reference number if it encounters duplicate
        while (RealEstatePayment::where('transaction_id', $generateTransactionID)->exists()) {
            $generateTransactionID = Str::upper(Str::random(10));
        }

        /**
         * Save transaction
         */

        $params = [
            'transaction_id' => $generateTransactionID,
            // 'client_id' => $request->client_id,
            'client_number' => $request->account_number,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email_address,
            'contact_number' => $request->contact_number,
            'sales_agent' => $request->sales,
            'sales_manager' => $request->sales_manager,
            'currency' => 'PHP',
            'payment_amount' => $request->amount,
            'payment_gateway' => $request->gateway,
            'payment_type' => $request->payment_type,
            // 'payment_channel' => $request->payment_channel,
            'payment_encode_type' => 'online_payment',
            // 'payment_gateway_reference_number' => $request->payment_gateway_reference_number,
            'remarks' => $request->remarks,
        ];

        if( in_array($request->payment_type, ['others', 'hoa_fees', 'camaya_air_payment']) ) {
            $params['is_verified'] = 1;
        }

        $newPayment = RealEstatePayment::create($params);

        $newPayment->paymentStatuses()->create([
            'status' => 'PENDING',
            'message' => 'PayMaya pending payment.',
        ]);

        // $newPayment->update([
        //     'paid_at' => Carbon::now(),
        //     'payment_gateway_reference_number' => $request->refno
        // ]);

        $public_key = base64_encode(env('PAYMENT_GATEWAY_PAYMAYA_KEY').':');

        $data = [
            "totalAmount" => [
              "value" => number_format($request->amount, 2, '.', ''),
              "currency" => "PHP",
              "details" => [
                "discount" => 0,
                "serviceCharge" => 0,
                "shippingFee" => 0,
                "tax" => 0,
                "subtotal" => number_format($request->amount, 2, '.', ''),
              ]
            ],
            "buyer" => [
              "firstName" => $request->first_name,
              "middleName" => $request->middle_name,
              "lastName" => $request->last_name,
            //   "birthday" => "1995-10-24",
            //   "customerSince" => "1995-10-24",
            //   "sex" => "M",
              "contact" => [
                "phone" => $request->contact_number,
                "email" => $request->email_address
              ],
              "shippingAddress" => [
                "firstName" => $request->first_name,
                "middleName" => $request->middle_name,
                "lastName" => $request->last_name,
                "phone" => $request->contact_number,
                "email" => $request->email_address,
                // "line1" => "",
                // "line2" => "",
                // "city" => "",
                // "state" => "",
                // "zipCode" => "",
                "countryCode" => "PH",
                "shippingType" => "ST" // ST - for standard, SD - for same day
              ],
              "billingAddress" => [
                "line1" => "",
                "line2" => "",
                "city" => "",
                "state" => "",
                "zipCode" => "",
                "countryCode" => "PH",
              ]
            ],
            "items" => [
              [
                "name" => $request->payment_type. " " .$request->client_number,
                "quantity" => 1,
                "code" => $generateTransactionID. " " . $request->payment_type,
                // "description" => $request->payment_type,
                'description' => 'RE - '.$request->payment_type.' - '. ($request->client_number ? $request->client_number : 'NA') .' - '. $request->first_name . " " . $request->last_name,
                // Add client number in description
                "amount" => [
                  "value" => $request->amount,
                  "details" => [
                    "discount" => 0,
                    "serviceCharge" => 0,
                    "shippingFee" => 0,
                    "tax" => 0,
                    "subtotal" => number_format($request->amount, 2, '.', ''),
                  ]
                ],
                "totalAmount" => [
                  "value" => number_format($request->amount, 2, '.', ''),
                  "details" => [
                    "discount" => 0,
                    "serviceCharge" => 0,
                    "shippingFee" => 0,
                    "tax" => 0,
                    "subtotal" => number_format($request->amount, 2, '.', ''),
                  ]
                ]
              ]
            ],
            "redirectUrl" => [
              "success" => env('REAL_ESTATE_PAYMENT_PORTAL_URL').'/payment-successful?transaction_id='.$generateTransactionID,
              "failure" => env('REAL_ESTATE_PAYMENT_PORTAL_URL').'/payment-failed',
              "cancel" => env('REAL_ESTATE_PAYMENT_PORTAL_URL').'/payment-cancel',
            ],
            "requestReferenceNumber" => $generateTransactionID,
            "metadata" => [
                'client_number' => $request->client_number,
                "transaction_id" => $generateTransactionID,
                'sales_agent' => $request->sales,
                'sales_manager' => $request->sales_manager,
                'remarks' => $request->remarks,
            ]
        ];


        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.$public_key,
        ])->post(env('PAYMENT_GATEWAY_PAYMAYA_URL'), $data);

        // return $response->json();
        $json_response = $response->json();

        if ($response->ok()) {
            return redirect()->away($json_response['redirectUrl']);
        }

    }

    public function deletePayMayaWebhooks(Request $request) {

        $secret_key = base64_encode(env('PAYMENT_GATEWAY_PAYMAYA_ID').':');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.$secret_key,
        ])->delete(env('PAYMENT_GATEWAY_PAYMAYA_WEBHOOK').'/'.$request->id);

    }

    public function payMayaWebhookCallback(Request $request)
    {

        $secret_key = base64_encode(env('PAYMENT_GATEWAY_PAYMAYA_ID').':');

        $transaction_id = $request->id;

        if (!$transaction_id) {
            return ['status' => false, 'message' => 'Transaction Id Missing'];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.$secret_key,
        ])->get(env('PAYMENT_GATEWAY_PAYMAYA_URL').'/'.$request->id);

        $json_response = $response->json();

        if ($response->ok()) {

            if (!isset($request['metadata']['payment_portal']) || $request['metadata']['payment_portal'] != 'golf_membership_portal') {
                $payment_transaction = RealEstatePayment::where('transaction_id', $request['metadata']['transaction_id'])->first();
            } else {
                $payment_transaction = true;
            }

            if ($payment_transaction) {

                switch ($json_response['paymentStatus']) {
                    case 'PAYMENT_SUCCESS':

                        if (!isset($request['metadata']['payment_portal']) || $request['metadata']['payment_portal'] != 'golf_membership_portal') {
                            $payment_transaction->paymentStatuses()->create([
                                'status' => 'SUCCESS',
                                'message' => 'PayMaya success payment.',
                            ]);

                            $payment_transaction->update([
                                'paid_at' => Carbon::now(),
                                'payment_gateway_reference_number' => $transaction_id,
                            ]);

                            $subject = "[SUCCESS] Camaya Online Payment Confirmation | Transaction ID: ". $transaction_id . " (PAYMAYA)";

                            if ($subject && $payment_transaction->email) {
                                Mail::to($payment_transaction->email)
                                    ->send(new SuccessfulPaymentPayMaya($payment_transaction, $subject, $request->all()));
                            }
                        } else {
                            $transactions = PaymentTransaction::setTransactionStatusToPaid($request['metadata']['transaction_id'], $transaction_id);

                            $user = User::where('id', $transactions[0]->user_id)->first();
                            $payment_transactions = PaymentTransaction::where('transaction_id', $request['metadata']['transaction_id'])
                                                    ->select('transaction_id', 'item', 'payment_channel','amount')
                                                    ->get();

                            $total_amount = collect($payment_transactions)->sum('amount');
                            $items = collect($payment_transactions)->pluck('item');

                            // if ($request->provider == 'golf-paypal') {
                                $newGolfMember = 0;

                                if ($user->clientProfile->golf_membership == NULL) {
                                    $newGolfMember = 1;
                                }

                                // golf_membership_fee
                                // golf_membership_fee_promo

                                if ($items->contains('non_hoa_3yr_golf_membership_fee')
                                    || $items->contains('non_hoa_3yr_golf_membership_fee_promo')
                                    || $items->contains('golf_membership_fee')
                                    || $items->contains('golf_membership_fee_promo')) {
                                    ClientProfile::where('user_id', $user->id)->update([
                                        'golf_membership' => Carbon::now()->addYears(3),
                                    ]);

                                    $newGolfMember = $newGolfMember + 1;
                                }else if ($items->contains('non_hoa_golf_membership_fee_2021')) {
                                    ClientProfile::where('user_id', $user->id)->update([
                                        // 'golf_membership' => Carbon::now()->addYears(1),
                                        'golf_membership' => Carbon::createFromDate(2024, 2, 29, 'Asia/Manila'),
                                    ]);

                                    $newGolfMember = $newGolfMember + 1;
                                }
                            // }

                            // Redirect to payments page for confirmation
                            echo "Confirming payment. DO NOT CLOSE.";

                            // Notify via email
                            Mail::to($user->email)->send(new GolfPaymentTransactionMail($user, $transactions));


                            $message['text'] = $user->first_name." ".$user->last_name." has paid P".number_format($total_amount,0)." for [". implode(' ,', $items->all())."]";

                            if (env('VIBERBOT_NOTIFICATION') == true) {
                                ViberSubscriber::broadcastMessage($message,'text');
                            }

                            // return redirect($url.'/?status=confirmed&t='.$request->transaction_id.($request->provider == 'golf-paypal' || $request->provider == 'paypal' && $newGolfMember == 2 ? '&new_golf_member=1':''));
                        }
                        break;

                    case 'PAYMENT_FAILED':
                            $payment_transaction->paymentStatuses()->create([
                                'status' => 'FAILED',
                                'message' => 'PayMaya failed payment.',
                            ]);
                        break;

                    case 'PAYMENT_EXPIRED':
                            $payment_transaction->paymentStatuses()->create([
                                'status' => 'EXPIRED',
                                'message' => 'PayMaya failed payment.',
                            ]);
                        break;
                }

            }
        }

        return $response->json();
    }

    public function paymentVerification(Request $request)
    {

        $transaction_id = $request->transaction_id;
        $is_verify = $request->is_verified;
        $user_id = $request->user()->id;
        $payment_type = $request->payment_type;
        $client_number = $request->client_number;
        $reservation_number = $request->reservation_number;
        $advance_payment = $request->advance_payment;
        $reservation = false;

        $reservation = Reservation::whereNotNull('client_number')
            ->where('client_number', $client_number)
            ->where('client_number', '!=', '')->first();

        if( !$reservation ) {
            $reservation = Reservation::whereNotNull('reservation_number')
                ->where('reservation_number', $reservation_number)
                ->where('reservation_number', '!=', '')->first();

            if( $payment_type == 'penalty' || $payment_type == 'monthly_amortization_payment' ) {
                Reservation::where('reservation_number', $reservation_number)->update([
                    'recalculated' => 0
                ]);
            }
        } else {
            if( $payment_type == 'penalty' || $payment_type == 'monthly_amortization_payment' ) {
                Reservation::where('reservation_number', $reservation_number)->update([
                    'recalculated' => 0
                ]);
            }
        }

        if( $reservation ) {

            $payment = RealEstatePayment::where('transaction_id', $transaction_id)
                ->with(['paymentStatuses' => function ($query) {
                    $query->orderBy('created_at', 'DESC');
                }])
                ->with('reservation')
                ->with('verifiedBy')
                ->orderBy('created_at', 'DESC')
                ->limit(1);

            $payment_data = [
                'is_verified' => $is_verify,
                'verified_by' => $user_id,
                'verified_date' => Carbon::now(),
                'advance_payment' => $advance_payment
            ];

            if( $reservation_number === null || $reservation_number === '' ) {
                if( $reservation->reservation_number !== null || $reservation->reservation_number !== '' ) {
                    $payment_data['reservation_number'] = $reservation->reservation_number;
                }
            }

            if( $client_number === null || $client_number === '' ) {
                if( $reservation->client_number !== null || $reservation->client_number !== ''  ) {
                    $payment_data['client_number'] = $reservation->client_number;
                }
            }

            $payment->where('transaction_id', $transaction_id)->update($payment_data);

            RealestatePaymentActivityLog::create([
                'action' => 'verify_payment',
                'description' => 'Verify payment',
                'model' => 'App\Models\RealEstate\RealestatePaymentActivityLog',
                'properties' => null,
                'created_by' => $user_id,
            ]);

            return $payment->get();

        } else {
            return response()->json(['message' => 'No reservation agreement found.'], 400);
        }

    }

    public function verify_payment(Request $request)
    {
        $payment = RealEstatePayment::where('transaction_id', $request->transaction_id)->limit(1)->first();

        if( $payment->count() > 0 ) {
            RealEstatePayment::where('transaction_id', $request->transaction_id)->limit(1)->update([
                'is_verified' => 1
            ]);
            dd(RealEstatePayment::where('transaction_id', $request->transaction_id)->limit(1)->first());
        }
    }

}
