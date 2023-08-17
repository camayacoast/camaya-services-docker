<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use App\Models\Booking\GeneratedVoucher;

use Omnipay\Omnipay;
use Omnipay\PayPal\PayPalItemBag;
use Carbon\Carbon;

class VoucherPaymentRequest extends Controller
{
    public $gateway;
 
    public function __construct()
    {
        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId(env('PAYPAL_CLIENT_ID'));
        $this->gateway->setSecret(env('PAYPAL_CLIENT_SECRET'));
        $this->gateway->setTestMode(env('APP_ENV') == 'production' ? false : true); //set it to 'false' when go live
        // $this->gateway->setBrandName('Camaya Booking Kit');
    }
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        
        switch ($request->provider) {
            case 'paymaya':
                $this->paymaya($request);
                break;
            
            default:
                $this->paypal($request);
                break;
        }
    }

    public function paymaya($request)
    {
        if (env('BOOKING_PAYMENT_GATEWAY_PAYMAYA_OPEN') == false) return response()->json(['message' => 'PayMaya Payment Gateway is closed.'], 200);

        $generated_vouchers = GeneratedVoucher::with('voucher')->with('customer')->where('transaction_reference_number', $request->transaction_reference_number)->whereNull('paid_at')->get();

        if (!$generated_vouchers) return 'Transaction could not proceed.';

        try {
            $items = [];
            foreach( $generated_vouchers as $generated_voucher ) {

                $items[] = [
                    "name" => $generated_voucher->voucher->name,
                    "quantity" => '1',
                    "code" => $generated_voucher->voucher->code,
                    'description' => 'Camaya Vouchers '.$generated_voucher->transaction_reference_number.' - '.$generated_voucher->voucher_code,
                    "amount" => [
                        "value" => number_format($generated_voucher->voucher->price, 2, '.', ''),
                        "details" => [
                            "discount" => 0,
                            "serviceCharge" => 0,
                            "shippingFee" => 0,
                            "tax" => 0,
                            "subtotal" => number_format($generated_voucher->voucher->price, 2, '.', ''),
                        ]
                    ],
                    "totalAmount" => [
                        "value" => number_format($generated_voucher->voucher->price, 2, '.', ''),
                        "details" => [
                            "discount" => 0,
                            "serviceCharge" => 0,
                            "shippingFee" => 0,
                            "tax" => 0,
                            "subtotal" => number_format($generated_voucher->voucher->price, 2, '.', ''),
                        ]
                    ]
                ];
            }

            $data = [
                "totalAmount" => [
                    "value" => number_format(collect($generated_vouchers)->sum('price'), 2, '.', ''),
                    "currency" => "PHP",
                    "details" => [
                        "discount" => 0,
                        "serviceCharge" => 0,
                        "shippingFee" => 0,
                        "tax" => 0,
                        "subtotal" => number_format(collect($generated_vouchers)->sum('price'), 2, '.', ''),
                    ],
                ],
                
                "buyer" => [
                    "firstName" => $generated_vouchers[0]->customer->first_name,
                    "middleName" => $generated_vouchers[0]->customer->middle_name,
                    "lastName" => $generated_vouchers[0]->customer->last_name,
                    "contact" => [
                      "phone" => $generated_vouchers[0]->customer->contact_number,
                      "email" => $generated_vouchers[0]->customer->email_address
                    ],
                    "shippingAddress" => [
                      "firstName" => $generated_vouchers[0]->customer->first_name,
                      "middleName" => $generated_vouchers[0]->customer->middle_name,
                      "lastName" => $generated_vouchers[0]->customer->last_name,
                      "phone" => $generated_vouchers[0]->customer->contact_number,
                      "email" => $generated_vouchers[0]->customer->email_address,
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
                "items" => $items,
                "redirectUrl" => [
                    // "success" => env('APP_URL').'/api/booking/public/payment/paymaya/success/' . $booking->reference_number,
                    "success" => url('api/booking/public/voucher-payment/'.$request->provider.'/success', [$request->transaction_reference_number]),
                    "failure" => url('api/booking/public/voucher-payment/'.$request->provider.'/failed', [$request->transaction_reference_number]),
                    "cancel" => url('api/booking/public/voucher-payment/'.$request->provider.'/cancel', [$request->transaction_reference_number]),
                ],
                "requestReferenceNumber" => $generated_vouchers[0]->transaction_reference_number
            ];

            $public_key = base64_encode(env('MAYA_PUBLIC_API_KEY').':');
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $public_key,
                'Accept' => 'application/json',
            ])->post(env('MAYA_API_ENDPOINT'), $data);

            $json_response = $response->json();

            if ($response->ok()) {

                $update = GeneratedVoucher::where('transaction_reference_number', $request->transaction_reference_number )
                    ->update(['checkout_id' => $json_response['checkoutId']]);

                return redirect()->to($json_response['redirectUrl'])->send();
            }

        } catch(Exception $e) {
            return $e->getMessage();
        }
        
    }

    public function paypal($request)
    {
        $generated_vouchers = GeneratedVoucher::with('voucher')->where('transaction_reference_number', $request->transaction_reference_number)->whereNull('paid_at')->get();
                
        if (!$generated_vouchers) {
            return 'Transaction could not proceed.';
        }

        try {

            $items = new PayPalItemBag;

            // ->setName(strtoupper($type)." - ".$booking->code)
            // ->setDescription('Camaya Coast '.$booking->code)

            foreach ($generated_vouchers as $generated_voucher) {

                $items->add([
                    'sku' => $generated_voucher->voucher->code,
                    'name' => $generated_voucher->transaction_reference_number."-".$generated_voucher->voucher->code,
                    'description' => 'Camaya Vouchers '.$generated_voucher->transaction_reference_number.' - '.$generated_voucher->transaction_reference_number,
                    'quantity' => 1,
                    'price' => $generated_voucher->price,
                    'currency' => 'PHP'
                ]);

            }

            $response = $this->gateway->purchase(array(
                'amount' => collect($generated_vouchers)->sum('price'),
                'name'  => 'Camaya Voucher Purchase',
                'description' => $request->transaction_reference_number,
                'currency' => 'PHP',
                'returnUrl' => url('api/booking/public/voucher-payment/'.$request->provider.'/success', [$request->transaction_reference_number]),
                // 'returnUrl' => route('paypal.paymentStatus',['invoice_id' => $invoice->id]),
                'cancelUrl' => url('api/booking/public/voucher-payment/'.$request->provider.'/cancel', [$request->transaction_reference_number]),
            ))
            ->setItems($items)
            ->send();

            if ($response->isRedirect()) {
                $response->redirect(); // this will automatically forward the customer
            } else {
                // not successful
                return $response->getMessage();
            }
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }
}
