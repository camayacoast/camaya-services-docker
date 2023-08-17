<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Omnipay\Omnipay;

use App\Models\Booking\GeneratedVoucher;
use App\Models\Booking\ActivityLog;

use Carbon\Carbon;

use App\Mail\Booking\VoucherConfirmation;
use Illuminate\Support\Facades\Mail;

use PayMaya\PayMayaSDK;
use PayMaya\Core\CheckoutAPIManager;

class VoucherPaymentSuccess extends Controller
{
    public $gateway;
 
    public function __construct()
    {
        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId(env('PAYPAL_CLIENT_ID'));
        $this->gateway->setSecret(env('PAYPAL_CLIENT_SECRET'));
        // $this->gateway->setTestMode(true); //set it to 'false' when go live
        $this->gateway->setTestMode(env('APP_ENV') == 'production' ? false : true); //set it to 'false' when go live
    }
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // return $request->all();

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
        $newPaymentReferenceNumber = "VP-".\Str::upper(\Str::random(6));
        while (GeneratedVoucher::where('payment_reference_number', $newPaymentReferenceNumber)->exists()) {
            $newPaymentReferenceNumber = "VP-".\Str::upper(\Str::random(6));
        }

        $checkIfFullyPaid = GeneratedVoucher::whereNull('paid_at')->where('transaction_reference_number', $request->transaction_reference_number)->get();
        
        if (collect($checkIfFullyPaid)->sum('price') <= 0.00) {
            return response()->json([
                'message' => 'Vouchers are fully paid.'
            ], 200);
        }

        PayMayaSDK::getInstance()->initCheckout(env('MAYA_PUBLIC_API_KEY'), env('MAYA_SECRET_API_KEY'), env('MAYA_API_ENDPOINT_ENV'));

        $APIManager = new CheckoutAPIManager();
        $response = $APIManager->retrieveCheckout($checkIfFullyPaid[0]->checkout_id);
        $responseArr = json_decode($response, true);

        GeneratedVoucher::whereNull('paid_at')
            ->where('transaction_reference_number', $request->transaction_reference_number)
            ->update([
                'provider_reference_number' => $responseArr['transactionReferenceNumber'],
                'payment_reference_number' => $newPaymentReferenceNumber,
                'voucher_status' => 'active',
                'payment_status' => 'paid',
                'provider' => $request->provider,
                'mode_of_payment' => 'online_payment',
                'paid_at' => Carbon::now(),
            ]);
        
        $paid_vouchers = GeneratedVoucher::with('voucher')->with('customer')->whereNotNull('paid_at')->where(
            'transaction_reference_number', $request->transaction_reference_number
        )->get();

        Mail::to($paid_vouchers[0]->customer->email)->send(new VoucherConfirmation($paid_vouchers, $request->transaction_reference_number));

        return redirect()->to(
            env('CAMAYA_BOOKING_PORTAL_URL').'/voucher-payment-successful?transaction_reference_number='.$request->transaction_reference_number
        )->send();
    }

    public function paypal($request)
    {
        /**
         * Generate New Unique Payment Reference Number
         */ 
        $newPaymentReferenceNumber = "VP-".\Str::upper(\Str::random(6));

        // Creates a new reference number if it encounters duplicate
        while (GeneratedVoucher::where('payment_reference_number', $newPaymentReferenceNumber)->exists()) {
            $newPaymentReferenceNumber = "VP-".\Str::upper(\Str::random(6));
        }
        
        $checkIfFullyPaid = GeneratedVoucher::whereNull('paid_at')->where('transaction_reference_number', $request->transaction_reference_number)->get();

        if (collect($checkIfFullyPaid)->sum('price') <= 0.00) {
            return "Vouchers are fully paid.";
        }
        //
        // Once the transaction has been approved, we need to complete it.
        if ($request->input('paymentId') && $request->input('PayerID'))
        {
            $transaction = $this->gateway->completePurchase(array(
                'payer_id'             => $request->input('PayerID'),
                'transactionReference' => $request->input('paymentId'),
            ));

            $response = $transaction->send();
         
            if ($response->isSuccessful())
            {
                // The customer has successfully paid.
                $arr_body = $response->getData();
         
                // Update transaction data into the database
                GeneratedVoucher::whereNull('paid_at')
                    ->where('transaction_reference_number', $request->transaction_reference_number)
                    ->update([
                        'provider_reference_number' => $arr_body['id'],
                        'payment_reference_number' => $newPaymentReferenceNumber,
                        'voucher_status' => 'active',
                        'payment_status' => 'paid',
                        'provider' => $request->provider,
                        'mode_of_payment' => 'online_payment',
                        'paid_at' => Carbon::now(),
                    ]);


                $paid_vouchers = GeneratedVoucher::with('voucher')->with('customer')->whereNotNull('paid_at')->where('transaction_reference_number', $request->transaction_reference_number)
                                        ->get();

                if ($paid_vouchers) {

                    // Create log
                    // ActivityLog::create([
                    //     // 'booking_reference_number' => $bookingReferenceNumber->booking_reference_number,

                    //     'action' => 'online_payment',
                    //     'description' => 'Online Payment has been made using PayPal with the amount of P'.$payment->amount.'.',
                    //     'model' => 'App\Models\Booking\GeneratedVoucher',
                    //     // 'model_id' => $invoice->id,
                    //     'properties' => null,

                    //     'created_by' => null,
                    // ]);

                    
                    Mail::to($paid_vouchers[0]->customer->email)
                                        ->send(new VoucherConfirmation($paid_vouchers, $request->transaction_reference_number));

                    return redirect()->away(env('CAMAYA_BOOKING_PORTAL_URL').'/voucher-payment-successful?transaction_reference_number='.$request->transaction_reference_number);
                    // return "Payment is successful. Your transaction id is: ". $arr_body['id']; // $arr_body['id']
                }

                return "Payment is successful. Your transaction id is: ". $arr_body['id'];

                // return response()->json($arr_body);
        
            } else {
                return $response->getMessage();
            }
        } else {
            return 'Transaction is declined';
        }
    }
}
