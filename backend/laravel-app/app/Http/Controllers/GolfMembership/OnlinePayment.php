<?php

namespace App\Http\Controllers\GolfMembership;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;
use App\Mail\GolfPaymentTransaction as GolfPaymentTransactionMail;

use Config;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

use App\PaymentTransaction;
use App\User;
use App\ViberSubscriber;
use App\ClientProfile;

use PayMaya\PayMayaSDK;
use PayMaya\API\Webhook;
use PayMaya\API\Checkout;
use PayMaya\Model\Checkout\Item;
use PayMaya\Model\Checkout\ItemAmount;
use PayMaya\Model\Checkout\ItemAmountDetails;

use DB;

class OnlinePayment extends Controller
{
    //

    protected $items = [
        'hoa_membership_fee' => 5000,
        'hoa_monthly_dues' => 10,
        'hoa_monthly_dues_promo' => 7*12,
        'hoa_monthly_dues_6months_promo' => 7*6,
        'fmf_privilege_activation_fee' => 20000,
        'fmf_monthly_dues' => 750,
        'fmf_monthly_dues_promo' => 500*12,
        'golf_membership_fee' => 100000,
        'golf_monthly_dues' => 1999,
        'golf_membership_fee_promo' => 30000,
        'golf_monthly_dues_promo' => 999*12,

        'non_hoa_3yr_golf_membership_fee' => 150000,
        'non_hoa_3yr_golf_membership_fee_promo' => 99000,
        'non_hoa_golf_monthly_dues' => 1999,
        'non_hoa_3yr_golf_dues_promo' => 999*36,
        'non_hoa_golf_annual_dues_promo' => 1499*12,

        'non_hoa_golf_membership_fee_2021' => 99000,
        'non_hoa_advance_golf_dues_12_months' => 999*12,
    ];

    public function __invoke(Request $request)
    {
        // return $request->all();

        if (env('CAMAYA_PAYMENT_PORTAL_ENABLE') == false) {
            return "Payment portal closed.";
        }

        $validator = Validator::make($request->all(), [
            // '' => 'required',
            'amount' => 'required|integer|between:750,500000',
        ]);

        $area = count($request->user()->clientProperties) ? $request->user()->clientProperties[0]['area'] : '';


        // initialize amount
        $total_amount = 0;

        // Sum it up
        foreach ($request->items as $item) {
            if ($item == 'hoa_monthly_dues' || $item == 'hoa_monthly_dues_promo' || $item == 'hoa_monthly_dues_6months_promo') {
                $total_amount = $total_amount + $this->items[$item] * $area;
            } else if ($item == 'non_hoa_3yr_golf_membership_fee' || $item == 'non_hoa_3yr_golf_membership_fee_promo') {
                $total_amount = $total_amount + ($this->items[$item] * ($request->payment_option == 'FULL' ? 0.9 : 0.3));
            } else {
                $total_amount = $total_amount + $this->items[$item];
            }
        }

        // Check if the total_amount ordered is equal to the item database
        if ($total_amount != $request->amount) {
            return 'An error occured please make another transaction.';
        }

        if ($total_amount < 750 || $total_amount > 500000) {
            return 'Amount out of range.';
        }

        if ($validator->fails()) {
            
            echo "<div style=\"display: flex; justify-content: center; align-items: center; height: 100vh;\">";
                echo "<div style=\"font-family: 'Arial', sans-serif; padding: 50px; border: solid 1px gainsboro;\">";
                echo "<p>Your payment could not continue.</p>";

                foreach($validator->errors()->all() as $error) {
                echo $error;
                }

                echo "<p style='margin-top: 50px'><a href='".env('CAMAYA_GOLF_PORTAL')."'>Go back to golf membership payment portal</a></p>";
                echo "</div>";
            echo "</div>";

            exit;
        }

        $createTransactionId = substr(strtoupper(md5(rand() * time())), 0, 10);

        $filtered_items = collect($this->items)->only($request->items);
    
        $paymaya_items = [];
        $item_transactions = [];

        foreach ($filtered_items as $key => $amount) {
    
            $createItemTransactionId = substr(strtoupper(md5(rand() * time())), 0, 20);
            $price = (($key == 'non_hoa_3yr_golf_membership_fee' || $key == 'non_hoa_3yr_golf_membership_fee_promo') ? $this->items[$key] * ($request->payment_option == 'FULL' ? 0.9:0.3) : $this->items[$key]);

            $paymaya_items[] = [
                "name" => 'Payment: '. $key .' - '. $createTransactionId .' - '. $createItemTransactionId .' - '. $request->user()->first_name .' '. $request->user()->last_name,
                "quantity" => 1,
                "code" => 'Item: '. $key,
                // "description" => $request->payment_type,
                'description' => 'GOLF - '.$key .' - '. $createTransactionId .' - '. $createItemTransactionId .' - '. $request->user()->first_name .' '. $request->user()->last_name,
                // Add client number in description
                "amount" => [
                  "value" => $amount,
                  "details" => [
                    "discount" => 0,
                    "serviceCharge" => 0,
                    "shippingFee" => 0,
                    "tax" => 0,
                    "subtotal" => number_format($amount, 2, '.', ''),
                  ]
                ],
                "totalAmount" => [
                  "value" => number_format($amount, 2, '.', ''),
                  "details" => [
                    "discount" => 0,
                    "serviceCharge" => 0,
                    "shippingFee" => 0,
                    "tax" => 0,
                    "subtotal" => number_format($amount, 2, '.', ''),
                  ]
                ]
            ];

            $item_transactions[] = [
                'transaction_id' => $createTransactionId,
                'item_transaction_id' => $createItemTransactionId,
                'item' => $key,
                'status' => 'created',
                'payment_channel' => 'paymaya',
                'remarks' => '',
                'amount' => $price
            ];

            // $paymaya_items[] = $newItem;
        }

        //////////

        $user = User::find($request->user()->id);

        $payment_history = [];

        foreach ($item_transactions as $i) {
            $payment_history[] = new PaymentTransaction([
                'transaction_id' => $i['transaction_id'],
                'item_transaction_id' => $i['item_transaction_id'],
                'item' => $i['item'],
                'status' => $i['status'],
                'payment_channel' => $i['payment_channel'],
                'remarks' => $i['remarks'],
                'amount' => $i['amount']
            ]);
        }

        $user->paymentHistory()->saveMany($payment_history);


        $public_key = base64_encode(env('PAYMENT_GATEWAY_PAYMAYA_KEY').':');

        $data = [
            "totalAmount" => [
              "value" => number_format($total_amount, 2, '.', ''),
              "currency" => "PHP",
              "details" => [
                "discount" => 0,
                "serviceCharge" => 0,
                "shippingFee" => 0,
                "tax" => 0,
                "subtotal" => number_format($total_amount, 2, '.', ''),
              ]
            ],
            "buyer" => [
              "firstName" => $request->user()->clientProfile->first_name,
              "middleName" => $request->user()->clientProfile->middle_name,
              "lastName" => $request->user()->clientProfile->last_name,
            //   "birthday" => "1995-10-24",
            //   "customerSince" => "1995-10-24",
            //   "sex" => "M",
              "contact" => [
                "phone" => $request->user()->clientProfile->contact_number,
                "email" => $request->user()->email
              ],
              "shippingAddress" => [
                "firstName" => $request->user()->clientProfile->first_name,
                "middleName" => $request->user()->clientProfile->middle_name,
                "lastName" => $request->user()->clientProfile->last_name,
                "phone" => $request->user()->clientProfile->contact_number,
                "email" => $request->user()->email,
                // "line1" => "",
                // "line2" => "",
                // "city" => "",
                // "state" => "",
                // "zipCode" => "",
                // "countryCode" => "",
                "shippingType" => "ST" // ST - for standard, SD - for same day
              ],
              "billingAddress" => [
                "line1" => "",
                "line2" => "",
                "city" => "",
                "state" => "",
                "zipCode" => "",
                "countryCode" => "",
              ]
            ],
            "items" => $paymaya_items,
            "redirectUrl" => [
            //   "success" => env('CAMAYA_GOLF_PORTAL').'/payment-successful?transaction_id='.$createTransactionId,
              "success" => env('CAMAYA_GOLF_PORTAL').'/?status=confirmed&t='.$createTransactionId.'&new_golf_member=1',
              "failure" => env('CAMAYA_GOLF_PORTAL').'/?status=failed',
              "cancel" => env('CAMAYA_GOLF_PORTAL').'/?status=cancelled&t='.$createTransactionId,
            ],
            "requestReferenceNumber" => $createTransactionId,
            "metadata" => [
                "transaction_id" => $createTransactionId,
                "payment_portal" => 'golf_membership_portal'
            ]
        ];


        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.$public_key,
        ])->post(env('PAYMENT_GATEWAY_PAYMAYA_URL'), $data);

        // return $response->json();
        $json_response = $response->json();

        if ($response->ok()) {
            return $json_response;
            return redirect()->away($json_response['redirectUrl']);
        }

        return 'error';

    }

}
