<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Invoice;
use App\Models\Booking\Booking;
use App\Models\Booking\Payment;

use Omnipay\Omnipay;
use Omnipay\PayPal\PayPalItemBag;
use Carbon\Carbon;

use Validator;

class PartialPaymentRequest extends Controller
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
        //
        $booking = Booking::where('reference_number', $request->booking_reference_number)->first();
        $invoices = Invoice::where('booking_reference_number', $request->booking_reference_number)
                            ->select(
                                'id',
                                'reference_number',
                                'batch_number',
                                'paid_at',
                                'status',
                                'due_datetime',
                                'grand_total',
                                'balance',
                            )
                            ->whereNull('paid_at')
                            ->get();

        $total_balance = collect($invoices)->sum('balance');

        if (!$invoices) {
            return 'All invoices are paid.';
        }

        if (!$booking) {
            return 'Booking not found';
        }

        if ($booking->status == 'cancelled') {
            return 'Booking is already cancelled.';
        }

        $validator = Validator::make([
            'amount' => $request->amount
        ], [
            // '' => 'required',
            'amount' => 'required|integer|between:1,'.$total_balance,
        ]);

        if ($validator->fails()) {
            return 'Invalid amount.';
        }

        try {

            $items = new PayPalItemBag;

            // ->setName(strtoupper($type)." - ".$booking->code)
            // ->setDescription('Camaya Coast '.$booking->code)

            /**
             * Generate New Unique Payment Reference Number
             */ 
            $newPaymentReferenceNumber = "P-".\Str::upper(\Str::random(6));

            // Creates a new reference number if it encounters duplicate
            while (Payment::where('payment_reference_number', $newPaymentReferenceNumber)->exists()) {
                $newPaymentReferenceNumber = "P-".\Str::upper(\Str::random(6));
            }

            foreach ($invoices as $invoice) {
                $items->add([
                    'sku' => $invoice->reference_number,
                    'name' => $invoice->reference_number."-".$invoice->batch_number,
                    'description' => 'Camaya Booking '.$booking->reference_number.' - '.$invoice->reference_number,
                    'quantity' => 1,
                    'price' => $request->amount > $invoice->balance ? $invoice->balance : $request->amount,
                    'currency' => 'PHP'
                ]);

                $payment = new Payment;
                // Create payment record pending

                $payment->booking_reference_number = $booking->reference_number;
                $payment->invoice_id = $invoice->id; //invoice_id here
                $payment->folio_id = null;
                $payment->inclusion_id = null;
                $payment->voucher_id = null;
                $payment->billing_instruction_id = null;
                $payment->payment_reference_number = $newPaymentReferenceNumber;
                $payment->mode_of_payment = 'online_payment';
                $payment->market_segmentation = '';
                $payment->status = 'pending';
                $payment->provider = 'paypal';
                $payment->provider_reference_number = null;
                $payment->amount = $request->amount > $invoice->balance ? $invoice->balance : $request->amount;
                $payment->remarks = '';
                $payment->paid_at = null;
                $payment->voided_by = null;
                $payment->voided_at = null;
                $payment->updated_at = null;
                $payment->created_by = null;

                $payment->save();
            }

            $total_amount = $request->amount < $total_balance ? $request->amount : $total_balance;

            $response = $this->gateway->purchase(array(
                'amount' => $total_amount,
                'name'  => 'Camaya Booking Purchase',
                'description' => $booking->reference_number,
                'currency' => 'PHP',
                'returnUrl' => url('api/booking/public/payment/'.$request->provider.'/success', [$newPaymentReferenceNumber]),
                // 'returnUrl' => route('paypal.paymentStatus',['invoice_id' => $invoice->id]),
                'cancelUrl' => url('api/booking/public/payment/'.$request->provider.'/cancel', [$newPaymentReferenceNumber]),
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
