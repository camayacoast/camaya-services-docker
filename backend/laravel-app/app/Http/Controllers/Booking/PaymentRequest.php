<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\OneBITS\Ticket;
use App\Models\Booking\Invoice;
use App\Models\Booking\Booking;
use App\Models\Booking\Payment;
use App\Models\Booking\Customer;
use App\Models\Hotel\RoomReservation;
use App\Models\Transportation\Trip;
use App\Models\Booking\ActivityLog;
use App\Mail\OneBITS\NewBooking;
use App\Mail\Booking\BookingConfirmation;
use App\Mail\Booking\BookingPaymentSuccessful;
use Illuminate\Support\Facades\Mail;

use PayMaya\PayMayaSDK;
use PayMaya\API\Checkout;
use PayMaya\Core\CheckoutAPIManager;
use App\Models\Transportation\SeatSegment;
use Omnipay\Omnipay;
use Omnipay\PayPal\PayPalItemBag;
use Carbon\Carbon;


class PaymentRequest extends Controller
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

        // Validate provider if set in the request
        if (!$request->provider) return response()->json(['message' => 'No payment provider selected'], 200);

        switch ($request->provider) {
            case 'paymaya':
                $this->PaymayaPayment($request);
                break;
            default:
                $this->PaypalPayment($request);
                break;
        }
    }

    public function getBooking($request)
    {
        return Booking::with('customer')
            ->with('inclusions')
            ->with('agent')
            ->with('sales_director')
            ->where('reference_number', $request->booking_reference_number)->first();
    }

    public function getInvoices($request)
    {
        return Invoice::where('booking_reference_number', $request->booking_reference_number)->select(
            'id',
            'reference_number',
            'batch_number',
            'paid_at',
            'status',
            'due_datetime',
            'grand_total',
            'balance',
        )->whereNull('paid_at')->get();
    }

    public function PaymayaPayment($request)
    {

        // Check environment variables for payment gateway status
        if (env('PAYMENT_GATEWAY_OPEN') == false) return response()->json(['message' => 'All Payment Gateways are closed.'], 200);

        if (env('BOOKING_PAYMENT_GATEWAY_PAYMAYA_OPEN') == false) return response()->json(['message' => 'PayMaya Payment Gateway is closed.'], 200);

        // Get booking and invoices details
        $booking = $this->getBooking($request);
        $invoices = $this->getInvoices($request);

        if (!$invoices) {
            echo json_encode(['message' => 'All invoices are paid.']);
            return false;
        }
        if (!$booking) {
            echo json_decode(['message' => 'Booking not found.']);
            return false;
        }
        if ($booking->status === 'cancelled') {
            echo json_encode(['message' => 'Booking is already cancelled.']);
            return false;
        }

        if (collect($invoices)->sum('balance') <= 0) {
            echo json_encode(['message' => 'All invoices are paid.'], 200);
            return false;
        }

        try {

            $newPaymentReferenceNumber = "P-" . Str::upper(Str::random(6));

            while (Payment::where('payment_reference_number', $newPaymentReferenceNumber)->exists()) {
                $newPaymentReferenceNumber = "P-" . Str::upper(Str::random(6));
            }

            foreach ($invoices as $invoice) {
                $newPayment = Payment::create([
                    'booking_reference_number' => $booking->reference_number,
                    'invoice_id' => $invoice->id,
                    'folio_id' => null,
                    'inclusion_id' => null,
                    'voucher_id' => null,
                    'billing_instruction_id' => null,
                    'payment_reference_number' => $newPaymentReferenceNumber,
                    'mode_of_payment' => 'online_payment',
                    'market_segmentation' => '',
                    'status' => 'pending',
                    'provider' => 'paymaya',
                    'provider_reference_number' => null,
                    'amount' => $invoice->balance,
                    'remarks' => '',
                    'paid_at' => null,
                    'voided_by' => null,
                    'voided_at' => null,
                    'updated_at' => null,
                    'created_by' => null,
                ]);
            }

            // Prepare inclusion item details
            $items = [];
            foreach ($booking->inclusions as $inclusion) {
                $items[] = [
                    "name" => $inclusion->item,
                    "quantity" => $inclusion->quantity,
                    "code" => $inclusion->code,
                    'description' => $inclusion->description,
                    "amount" => [
                        "value" => number_format($inclusion->price, 2, '.', ''),
                        "details" => [
                            "discount" => 0,
                            "serviceCharge" => 0,
                            "shippingFee" => 0,
                            "tax" => 0,
                            "subtotal" => number_format($inclusion->price, 2, '.', ''),
                        ]
                    ],
                    "totalAmount" => [
                        "value" => number_format($inclusion->price, 2, '.', ''),
                        "details" => [
                            "discount" => 0,
                            "serviceCharge" => 0,
                            "shippingFee" => 0,
                            "tax" => 0,
                            "subtotal" => number_format($inclusion->price, 2, '.', ''),
                        ]
                    ]
                ];
            }

            $data = [
                "totalAmount" => [
                    "value" => number_format(collect($invoices)->sum('balance'), 2, '.', ''),
                    "currency" => "PHP",
                    "details" => [
                        "discount" => 0,
                        "serviceCharge" => 0,
                        "shippingFee" => 0,
                        "tax" => 0,
                        "subtotal" => number_format(collect($invoices)->sum('balance'), 2, '.', ''),
                    ],
                ],

                "buyer" => [
                    "firstName" => $booking->customer->first_name,
                    "middleName" => $booking->customer->middle_name,
                    "lastName" => $booking->customer->last_name,
                    "contact" => [
                        "phone" => $booking->customer->contact_number,
                        "email" => $booking->customer->email
                    ],
                    "shippingAddress" => [
                        "firstName" => $booking->customer->first_name,
                        "middleName" => $booking->customer->middle_name,
                        "lastName" => $booking->customer->last_name,
                        "phone" => $booking->customer->contact_number,
                        "email" => $booking->customer->email,
                        "countryCode" => "PH",
                        "shippingType" => "ST" // ST - for standard, SD - for same day
                    ],
                    "billingAddress" => [
                        "line1" => $booking->customer->address,
                        "line2" => "",
                        "city" => "",
                        "state" => "",
                        "zipCode" => "",
                        "countryCode" => "PH",
                    ]
                ],
                "items" => $items,
                "redirectUrl" => [
                    "success" => env('APP_URL') . '/api/booking/public/payment/paymaya/success/' . $booking->reference_number . '/' . $newPaymentReferenceNumber,
                    "failure" => env('APP_URL') . '/api/booking/public/payment/paymaya/failed/' . $booking->reference_number,
                    "cancel" => env('APP_URL') . '/api/booking/public/payment/paymaya/cancel/' . $newPaymentReferenceNumber
                ],
                "requestReferenceNumber" => $newPaymentReferenceNumber,
                "metadata" => [
                    "transaction_id" => $newPaymentReferenceNumber,
                    "sales_agent" => ($booking->agent !== null) ? $booking->agent->email : '',
                    "sales_manager" => ($booking->sales_director !== null) ? $booking->sales_director->email : '',
                    "remarks" => $booking->remarks
                ]
            ];

            $public_key = base64_encode(env('MAYA_PUBLIC_API_KEY') . ':');

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $public_key,
                'Accept' => 'application/json',
            ])->post(env('MAYA_API_ENDPOINT'), $data);

            $json_response = $response->json();

            if ($response->ok()) {
                // New field checkout_id 6.6.2022
                // Update checkout_id for fetching transaction details in payment success API
                $update = Payment::where('booking_reference_number', $booking->reference_number)
                    ->where('payment_reference_number', $newPaymentReferenceNumber)
                    ->update(['checkout_id' => $json_response['checkoutId']]);


                return redirect()->to($json_response['redirectUrl'])->send();
            }
        } catch (Exception $e) {

            return $e->getMessage();
        }
    }

    public function booking_payment_check(Request $request)
    {
        $booking = Booking::where('reference_number', $request->booking_reference_number)->with('pending_payments')->first();
        $payments = Payment::where([
            'booking_reference_number' => $booking->reference_number,
        ])->get();
        dd($booking, $payments);
    }

    public function payment_status(Request $request)
    {
        PayMayaSDK::getInstance()->initCheckout(env('MAYA_PUBLIC_API_KEY'), env('MAYA_SECRET_API_KEY'), env('MAYA_API_ENDPOINT_ENV'));
        $booking = Booking::where('reference_number', $request->booking_reference_number)->with('pending_payments')->first();
        if ($booking) {
            $payments = $booking->pending_payments;
            $checkout_id = '';
            foreach ($payments as $payment) {
                if ($payment->checkout_id !== '') {
                    $checkout_id = $payment->checkout_id;
                }
            }
            echo 'Checkout ID: ' . $checkout_id;
            if ($booking && $checkout_id !== '') {
                $APIManager = new CheckoutAPIManager();
                $response = $APIManager->retrieveCheckout($checkout_id);
                $responseArr = json_decode($response, true);
                dd($responseArr);
            }
        }
    }

    public function paymayaWebhook(Request $request)
    {
        $endpoint = env('MAYA_API_ENDPOINT');
        $secret = base64_encode(env('MAYA_SECRET_API_KEY') . ':');
        $public = env('MAYA_PUBLIC_API_KEY');

        $transaction_id = $request->id;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $secret,
        ])->get($endpoint . '/' . $transaction_id);

        $json_response = $response->json();

        if ($response->ok()) {
            //onebits webhooks
            $transaction_id = $json_response['metadata']['transaction_id'];
            // if ($json_response['metadata']['application'] === "OneBits") {
            //     $tickets = Ticket::where('group_reference_number', $transaction_id)
            //         ->with('passenger')
            //         ->with('schedule.route.origin')
            //         ->with('schedule.route.destination')
            //         ->with('schedule.transportation')
            //         ->with('trip')
            //         ->get();
            //     $referenceNumber = $tickets[0]['reference_number'];
            //     if ($tickets && $referenceNumber !== '') {
            //         switch ($json_response['paymentStatus']) {
            //             case "PAYMENT_SUCCESS":
            //                 Ticket::where('group_reference_number', $transaction_id)->update([
            //                     'paid_at' => Carbon::now(),
            //                     'payment_status' => 'paid',
            //                     'payment_provider_reference_number' => $json_response['transactionReferenceNumber'],
            //                     'payment_provider' => 'maya',
            //                     'payment_channel' => $json_response['paymentScheme'],
            //                     'status' => 'paid',
            //                 ]);
            //                 $pluck_ticket_ref_no = collect($tickets)->pluck('reference_number')->all();
            //                 Trip::whereIn('ticket_reference_number', $pluck_ticket_ref_no)->update([
            //                     'status' => 'checked_in',
            //                     'checked_in_at' => Carbon::now(),
            //                 ]);
            //                 $passenger_email = $tickets[0]['email'];
            //                 Mail::to($passenger_email)
            //                     ->send(new NewBooking($response['reference_number'], $tickets));
            //                 return redirect(env('ONEBITS_URL') . '/book/payment/response?success=1&ref=' . $response['reference_number'])->with($response);
            //                 break;

            //             case 'PAYMENT_FAILED':
            //                 Ticket::where('group_reference_number', $transaction_id)->update([
            //                     'paid_at' => Carbon::now(),
            //                     'payment_status' => 'voided',
            //                     'payment_provider_reference_number' => $json_response['transactionReferenceNumber'],
            //                     'payment_provider' => 'maya',
            //                     'payment_channel' => $json_response['paymentScheme'],
            //                     'status' => 'voided',
            //                 ]);
            //                 $pluck_ticket_ref_no = collect($tickets)->pluck('reference_number')->all();
            //                 $trip = Trip::whereIn('ticket_reference_number', $pluck_ticket_ref_no)->where("status", "pending")->get();
            //                 $pluck_seat_segment_id = collect($trip)->pluck('seat_segment_id')->all();
            //                 Trip::whereIn('ticket_reference_number', $pluck_ticket_ref_no)->update([
            //                     'status' => 'cancelled',
            //                     'checked_in_at' => Carbon::now(),
            //                 ]);
            //                 SeatSegment::whereIn("id", $pluck_seat_segment_id)->decrement('used');
            //                 break;

            //             case 'PAYMENT_EXPIRED':
            //                 Ticket::where('group_reference_number', $transaction_id)->update([
            //                     'paid_at' => Carbon::now(),
            //                     'payment_status' => 'voided',
            //                     'payment_provider_reference_number' => $json_response['transactionReferenceNumber'],
            //                     'payment_provider' => 'maya',
            //                     'payment_channel' => $json_response['paymentScheme'],
            //                     'status' => 'voided',
            //                 ]);
            //                 $pluck_ticket_ref_no = collect($tickets)->pluck('reference_number')->all();
            //                 $trip = Trip::whereIn('ticket_reference_number', $pluck_ticket_ref_no)->where("status", "pending")->get();
            //                 $pluck_seat_segment_id = collect($trip)->pluck('seat_segment_id')->all();
            //                 Trip::whereIn('ticket_reference_number', $pluck_ticket_ref_no)->update([
            //                     'status' => 'cancelled',
            //                     'checked_in_at' => Carbon::now(),
            //                 ]);
            //                 SeatSegment::whereIn("id", $pluck_seat_segment_id)->decrement('used');
            //         }
            //     }
            //     return;
            // }
            $p = Payment::where('payment_reference_number', $json_response['metadata']['transaction_id'])->first();

            if ($p->status == 'pending') {

                $booking_reference_number = $p->booking_reference_number;

                switch ($json_response['paymentStatus']) {
                    case 'PAYMENT_SUCCESS':

                        $booking = Booking::where('reference_number', $booking_reference_number)->with('pending_payments')->first();
                        $invoices = Invoice::where('booking_reference_number', $booking_reference_number)->where('status', '!=', 'void')->get();
                        $payments = $booking->pending_payments;

                        $total_balance = collect($invoices)->sum('balance');
                        $total_payment_amount = collect($payments)->sum('amount');

                        $paymentUpdate = Payment::where([
                            'payment_reference_number' => $json_response['metadata']['transaction_id'],
                        ])->update([
                            'status' => 'confirmed',
                            'provider_reference_number' => $json_response['id'] ?? '',
                            'paid_at' => Carbon::now()
                        ]);

                        if ($total_balance <= $total_payment_amount) {
                            Booking::where('reference_number', $booking_reference_number)->update([
                                'status' => 'confirmed',
                                'approved_at' => Carbon::now(),
                                'approved_by' => $request->user() ? $request->user()->id : null,
                            ]);

                            RoomReservation::where('booking_reference_number', $booking_reference_number)
                                ->whereNotIn('status', ['cancelled', 'voided', 'transferred'])
                                ->update([
                                    'status' => 'confirmed'
                                ]);

                            Trip::where('booking_reference_number', $booking_reference_number)
                                ->where('status', 'pending')
                                ->update([
                                    'status' => 'checked_in'
                                ]);
                        }

                        foreach ($payments as $payment) {
                            $invoice = Invoice::where('id', $payment->invoice_id)->first();
                            $balance = (($invoice->balance - $payment->amount) <= 0.00) ? 0 : $invoice->balance - $payment->amount;
                            $change = (($payment->amount - $invoice->balance) > 0.00) ? ($payment->amount - $invoice->balance) : 0;

                            // preventing incrementation of payment every api visit.
                            $total_payment = ($invoice->status !== 'paid') ? $invoice->total_payment + $payment->amount : $invoice->total_payment;

                            Invoice::where('id', $payment->invoice_id)->update([
                                'total_payment' => $total_payment,
                                'balance' => $balance,
                                'status' => 'paid',
                                'paid_at' => ($balance <= 0.00) ? Carbon::now() : null,
                            ]);

                            ActivityLog::create([
                                'booking_reference_number' => $booking_reference_number,
                                'action' => 'online_payment',
                                'description' => 'Online Payment has been made using Paymaya with the amount of P' . $payment->amount . '.',
                                'model' => 'App\Models\Booking\Invoice',
                                'model_id' => $invoice->id,
                                'properties' => null,
                                'created_by' => null,
                            ]);
                        }

                        // Booking complete details
                        $bookingDetails = Booking::where('reference_number', $booking_reference_number)
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

                        break;

                    case 'PAYMENT_FAILED':
                        $paymentUpdate = Payment::where([
                            'payment_reference_number' => $json_response['metadata']['transaction_id'],
                        ])->update([
                            'status' => 'voided',
                        ]);
                        break;

                    case 'PAYMENT_EXPIRED':
                        $paymentUpdate = Payment::where([
                            'payment_reference_number' => $json_response['metadata']['transaction_id'],
                        ])->update([
                            'status' => 'voided'
                        ]);
                        break;
                        break;
                }
            }
        }

        return $json_response;
    }

    public function PaypalPayment($request)
    {
        $booking = $this->getBooking($request);
        $invoices = $this->getInvoices($request);

        if (!$invoices) {
            return 'All invoices are paid.';
        }
        if (!$invoices) {
            echo json_encode(['message' => 'All invoices are paid.']);
            return false;
        }

        if (collect($invoices)->sum('balance') <= 0) {
            echo json_encode(['message' => 'All invoices are paid.'], 200);
            return false;
        }

        if (!$booking) {
            echo json_encode(['message' => 'Booking not found.']);
            return false;
        }

        if ($booking->status == 'cancelled') {
            echo json_encode(['message' => 'Booking is already cancelled.']);
            return false;
        }

        try {

            $items = new PayPalItemBag;

            // ->setName(strtoupper($type)." - ".$booking->code)
            // ->setDescription('Camaya Coast '.$booking->code)

            /**
             * Generate New Unique Payment Reference Number
             */
            $newPaymentReferenceNumber = "P-" . \Str::upper(\Str::random(6));

            // Creates a new reference number if it encounters duplicate
            while (Payment::where('payment_reference_number', $newPaymentReferenceNumber)->exists()) {
                $newPaymentReferenceNumber = "P-" . \Str::upper(\Str::random(6));
            }

            foreach ($invoices as $invoice) {
                $items->add([
                    'sku' => $invoice->reference_number,
                    'name' => $invoice->reference_number . "-" . $invoice->batch_number,
                    'description' => 'Camaya Booking ' . $booking->reference_number . ' - ' . $invoice->reference_number,
                    'quantity' => 1,
                    'price' => $invoice->balance,
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
                $payment->amount = $invoice->balance;
                $payment->remarks = '';
                $payment->paid_at = null;
                $payment->voided_by = null;
                $payment->voided_at = null;
                $payment->updated_at = null;
                $payment->created_by = null;

                $payment->save();
            }

            $response = $this->gateway->purchase(array(
                'amount' => collect($invoices)->sum('balance'),
                'name'  => 'Camaya Booking Purchase',
                'description' => $booking->reference_number,
                'currency' => 'PHP',
                'returnUrl' => url('api/booking/public/payment/' . $request->provider . '/success', [$request->booking_reference_number, $newPaymentReferenceNumber]),
                // 'returnUrl' => route('paypal.paymentStatus',['invoice_id' => $invoice->id]),
                'cancelUrl' => url('api/booking/public/payment/' . $request->provider . '/cancel', [$newPaymentReferenceNumber]),
            ))
                ->setItems($items)
                ->send();

            if ($response->isRedirect()) {
                $response->redirect(); // this will automatically forward the customer
            } else {
                // not successful
                return $response->getMessage();
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
