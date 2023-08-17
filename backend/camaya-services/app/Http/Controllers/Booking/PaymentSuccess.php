<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Omnipay\Omnipay;

use App\Models\Booking\Booking;
use App\Models\Booking\Payment;
use App\Models\Booking\Invoice;
use App\Models\Booking\ActivityLog;

use App\Models\Hotel\RoomReservation;

use App\Models\Transportation\Trip;

use Carbon\Carbon;

use App\Mail\Booking\BookingConfirmation;
use App\Mail\Booking\BookingPaymentSuccessful;
use Illuminate\Support\Facades\Mail;

use PayMaya\PayMayaSDK;
use PayMaya\API\Checkout;
use PayMaya\Core\CheckoutAPIManager;

class PaymentSuccess extends Controller
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
                $this->PaymayaSuccess($request);
                break;
            default:
                $this->PaypalSuccess($request);
                break;
        }
    }

    public function PaymayaSuccess($request)
    {
        PayMayaSDK::getInstance()->initCheckout(env('MAYA_PUBLIC_API_KEY'), env('MAYA_SECRET_API_KEY'), env('MAYA_API_ENDPOINT_ENV'));
        
        $booking = Booking::where('reference_number', $request->booking_reference_number)->with('pending_payments')->first();
        $invoices = Invoice::where('booking_reference_number', $request->booking_reference_number)->where('status', '!=', 'void')->get();

        if( !$booking ) {
            echo json_encode(['message' => 'Booking not found.']);
            return false;
        }

        $total_balance = collect($invoices)->sum('balance');

        if( $booking ) {

            $payment_details = Payment::where('booking_reference_number', $request->booking_reference_number)
                ->where('payment_reference_number', $request->payment_reference_number)->first();

            $APIManager = new CheckoutAPIManager();
            $response = $APIManager->retrieveCheckout($payment_details->checkout_id);
            $responseArr = json_decode($response, true);

            if(!isset($responseArr['paymentStatus'])) {
                echo json_encode($responseArr);
                return false;
            }

            if( $responseArr['paymentStatus'] === 'PAYMENT_SUCCESS' ) {
        
                $paymentUpdate = Payment::where('booking_reference_number', $request->booking_reference_number)
                    ->where('payment_reference_number', $responseArr['requestReferenceNumber'])
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'confirmed',
                        'provider_reference_number' => $responseArr['transactionReferenceNumber'] ?? '',
                        'paid_at' => Carbon::now()
                    ]);
        
                $payments = Payment::where('booking_reference_number', $request->booking_reference_number)
                    ->where('payment_reference_number', $responseArr['requestReferenceNumber'])->get();

                $total_payment_amount = collect($payments)->sum('amount');

                if ($total_balance <= $total_payment_amount) {

                    Booking::where('reference_number', $request->booking_reference_number)->update([
                        'status' => 'confirmed',
                        'approved_at' => Carbon::now(),
                        'approved_by' => $request->user() ? $request->user()->id : null,
                    ]);

                    RoomReservation::where('booking_reference_number', $request->booking_reference_number)
                        ->whereNotIn('status', ['cancelled', 'voided', 'transferred'])
                        ->update([
                            'status' => 'confirmed'
                        ]);

                    Trip::where('booking_reference_number', $request->booking_reference_number)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'checked_in'
                        ]);
                }
        
                foreach( $payments as $payment ) {
        
                    $invoice = Invoice::where('id', $payment->invoice_id)->first();
                    $balance = (($invoice->balance - $payment->amount) <= 0.00) ? 0 : $invoice->balance - $payment->amount;
                    $change = (($payment->amount - $invoice->balance) > 0.00) ? ($payment->amount - $invoice->balance) : 0;

                    // preventing incrementation of payment every api visit.
                    $total_payment = ( $invoice->status !== 'paid' ) ? $invoice->total_payment + $payment->amount : $invoice->total_payment;
        
                    Invoice::where('id', $payment->invoice_id)->update([
                        'total_payment' => $total_payment,
                        'balance' => $balance,
                        'status' => 'paid',
                        'paid_at' => ($balance <= 0.00) ? Carbon::now() : null,
                    ]);
        
                    ActivityLog::create([
                        'booking_reference_number' => $request->booking_reference_number,
                        'action' => 'online_payment',
                        'description' => 'Online Payment has been made using Paymaya with the amount of P'.$payment->amount.'.',
                        'model' => 'App\Models\Booking\Invoice',
                        'model_id' => $invoice->id,
                        'properties' => null,
                        'created_by' => null,
                    ]);
                }

                // Booking complete details
                $bookingDetails = Booking::where('reference_number', $request->booking_reference_number)
                    ->with('bookedBy')
                    ->with('customer')
                    ->with(['guests' => function ($q) {
                        $q->with('tee_time.schedule');
                        $q->with('guestTags');
                        $q->with('tripBookings.schedule.transportation');
                        $q->with('tripBookings.schedule.route.origin');
                        $q->with('tripBookings.schedule.route.destination');
                    }])
                    ->with('inclusions.packageInclusions')
                    ->with('inclusions.guestInclusion')
                    ->with('invoices')
                    ->withCount(['invoices as invoices_grand_total' => function ($q) {
                        $q->select(\DB::raw('sum(grand_total)'));
                    }])
                    ->withCount(['invoices as invoices_balance' => function ($q) {
                        $q->select(\DB::raw('sum(balance)'));
                    }])
                    ->first();
        
                if ($bookingDetails->mode_of_transportation == 'own_vehicle') {
                    $bookingDetails->load('guestVehicles');
                }
        
                $camaya_transportations = [];
        
                if ($bookingDetails->mode_of_transportation == 'camaya_transportation') {
                    $bookingDetails->load('camaya_transportation');
        
                    $camaya_transportations = \App\Models\Transportation\Schedule::whereIn('trip_number', collect($bookingDetails['camaya_transportation'])->unique('trip_number')->pluck('trip_number')->all())
                        ->with('transportation')
                        ->with('route.origin')
                        ->with('route.destination')
                        ->get();
                }
                
                $additional_emails = [];

                if (isset($bookingDetails->additionalEmails)) {
                    $additional_emails = collect($bookingDetails->additionalEmails)->pluck('email')->all();
                }

                if ($total_balance <= $total_payment_amount) {

                    Mail::to($bookingDetails->customer->email)
                        ->cc($additional_emails)
                        ->send(new BookingConfirmation($bookingDetails, $camaya_transportations));

                } else {

                    Mail::to($bookingDetails->customer->email)
                        ->send(new BookingPaymentSuccessful($bookingDetails));

                }

                return redirect()->to(
                    env('CAMAYA_BOOKING_PORTAL_URL').'/payment-successful?booking_reference_number=' . $request->booking_reference_number
                )->send();

            } else {
                return response()->json([
                    'message' => 'Payment Status: ' + $responseArr['paymentStatus']
                ], 200);
            }

            
        } else {
            // Already catch via webhooks - redirect client to success page.
            return redirect()->to(
                env('CAMAYA_BOOKING_PORTAL_URL').'/payment-successful?booking_reference_number=' . $request->booking_reference_number
            )->send();
        }
    }

    public function PaypalSuccess($request)
    {
        $bookingReferenceNumber = Payment::where('payment_reference_number', $request->payment_reference_number)->select('booking_reference_number', 'invoice_id')->first();
        $invoices = Invoice::where('booking_reference_number', $bookingReferenceNumber['booking_reference_number'])
                                    ->where('status', '!=', 'void')
                                    ->get();

        if (collect($invoices)->sum('balance') <= 0.00) {

            // Update transaction data into the database
            // Commented due to changung payment cancelled if revisit this link and the booking is alread confirmed
            // Payment::where('payment_reference_number', $request->payment_reference_number)
            // ->update([
            //     'status' => 'cancelled',
            // ]);

            return "The booking is fully paid.";
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

                $isPaymentConfirmed = Payment::where('payment_reference_number', $request->payment_reference_number)
                                        ->where('status', 'confirmed')
                                        ->first();

                // return [
                //     $payments,
                //     $arr_body
                // ];

                if ($isPaymentConfirmed) {
                    return 'Already confirmed payment '.$arr_body['id'];
                }
         
                // Update transaction data into the database
                Payment::where('payment_reference_number', $request->payment_reference_number)
                        ->update([
                            'provider_reference_number' => $arr_body['id'],
                            'status' => 'confirmed',
                            'paid_at' => Carbon::now(),
                        ]);

                $total_balance = collect($invoices)->sum('balance');
                $payments = Payment::where('payment_reference_number', $request->payment_reference_number)
                                        ->where('status', 'confirmed')
                                        ->get();

                $total_payment_amount = collect($payments)->sum('amount');

                if ($total_balance <= $total_payment_amount) {
                    /**
                     * Confirm the booking
                     * Dec 7 2021 - confirm if fully paid
                     */
                    Booking::where('reference_number', $bookingReferenceNumber->booking_reference_number)->update([
                        'status' => 'confirmed',
                        'approved_at' => Carbon::now(),
                        'approved_by' => $request->user() ? $request->user()->id : null,
                    ]);

                    /**
                     * Confirm all room reservation if the booking has room reservations
                     * Dec 7 2021 - confirm if fully paid
                     */
                    RoomReservation::where('booking_reference_number', $bookingReferenceNumber->booking_reference_number)
                    ->whereNotIn('status', ['cancelled', 'voided', 'transferred'])
                    ->update([
                        'status' => 'confirmed'
                    ]);

                    /**
                     * Confirm all trips
                     * Dec 7 2021 - confirm if fully paid
                     */
                    Trip::where('booking_reference_number', $bookingReferenceNumber->booking_reference_number)
                    ->where('status', 'pending')
                    // ->whereNotIn('status', ['boarded', 'no_show'])
                    ->update([
                        'status' => 'checked_in'
                    ]);
                }

                if ($payments) {

                    foreach ($payments as $payment) {

                        // Payment::where('payment_reference_number', $request->payment_reference_number);
                        $invoice = Invoice::where('id', $payment->invoice_id)->first();

                        $balance = (($invoice->balance - $payment->amount) <= 0.00) ? 0 : $invoice->balance - $payment->amount;

                        $change = (($payment->amount - $invoice->balance) > 0.00) ? ($payment->amount - $invoice->balance) : 0;

                        // Update invoice
                        Invoice::where('id', $invoice->id)
                        ->update([
                            'total_payment' => $invoice->total_payment + $payment->amount,
                            'balance' => $balance,
                            // 'change' => $invoice->change + $change,
                            'status' => ($balance <= 0.00) ? 'paid' : 'partial',
                            'paid_at' => ($balance <= 0.00) ? Carbon::now() : null,
                        ]);

                        // Create log
                        ActivityLog::create([
                            'booking_reference_number' => $bookingReferenceNumber->booking_reference_number,

                            'action' => 'online_payment',
                            'description' => 'Online Payment has been made using PayPal with the amount of P'.$payment->amount.'.',
                            'model' => 'App\Models\Booking\Invoice',
                            'model_id' => $invoice->id,
                            'properties' => null,

                            'created_by' => null,
                        ]);

                    }

                    $booking = Booking::where('reference_number', $bookingReferenceNumber->booking_reference_number)
                                ->with('bookedBy')
                                ->with('customer')
                                ->with(['guests' => function ($q) {
                                    $q->with('tee_time.schedule');
                                    $q->with('guestTags');
                                    $q->with('tripBookings.schedule.transportation');
                                    $q->with('tripBookings.schedule.route.origin');
                                    $q->with('tripBookings.schedule.route.destination');
                                }])
                                ->with('inclusions.packageInclusions')
                                ->with('inclusions.guestInclusion')
                                ->with('invoices')
                                ->withCount(['invoices as invoices_grand_total' => function ($q) {
                                    $q->select(\DB::raw('sum(grand_total)'));
                                }])
                                ->withCount(['invoices as invoices_balance' => function ($q) {
                                    $q->select(\DB::raw('sum(balance)'));
                                }])
                                ->first();
                
                        if ($booking->mode_of_transportation == 'own_vehicle') {
                            $booking->load('guestVehicles');
                        }
                
                        $camaya_transportations = [];
                
                        if ($booking->mode_of_transportation == 'camaya_transportation') {
                            $booking->load('camaya_transportation');
                
                            $camaya_transportations = \App\Models\Transportation\Schedule::whereIn('trip_number', collect($booking['camaya_transportation'])->unique('trip_number')->pluck('trip_number')->all())
                                                ->with('transportation')
                                                ->with('route.origin')
                                                ->with('route.destination')
                                                ->get();
            
                        }
                    
                    $additional_emails = [];

                    if (isset($booking->additionalEmails)) {
                        $additional_emails = collect($booking->additionalEmails)->pluck('email')->all();
                    }

                    if ($total_balance <= $total_payment_amount) {

                        Mail::to($booking->customer->email)
                                            ->cc($additional_emails)
                                            ->send(new BookingConfirmation($booking, $camaya_transportations));

                    } else {

                        Mail::to($booking->customer->email)
                            // ->cc($additional_emails)
                            ->send(new BookingPaymentSuccessful($booking));

                    }

                    return redirect()->to(
                        env('CAMAYA_BOOKING_PORTAL_URL').'/payment-successful?booking_reference_number=' . $bookingReferenceNumber->booking_reference_number
                    )->send();
                    
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
