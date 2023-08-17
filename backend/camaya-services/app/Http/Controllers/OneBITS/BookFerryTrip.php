<?php

namespace App\Http\Controllers\OneBITS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Controllers\OneBITS\ValidateIfPassengerExists;
use App\Http\Controllers\OneBITS\ValidateCutOffTime;
use App\Models\OneBITS\Ticket;
use Illuminate\Http\JsonResponse;
use App\Models\Transportation\Passenger;
use App\Models\Transportation\Trip;
use App\Models\Transportation\SeatSegment;
use App\Models\Transportation\Seat;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Mail\OneBITS\PendingBooking;
use Illuminate\Support\Facades\Mail;


class BookFerryTrip extends Controller
{

    protected $_merchantCode;
    protected $_merchantKey;

    public function __construct()
    {
        // parent::__construct();
        $this->_merchantCode = env('IPAY88_MERCHANT_CODE'); //MerchantCode confidential
        $this->_merchantKey = env('IPAY88_MERCHANT_KEY'); //MerchantKey confidential
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function book(Request $request)
    {
        ///////// BEGIN TRANSACTION //////////
        $connection = DB::connection('camaya_booking_db');
        $connection->beginTransaction();

        $total_passengers = count($request->adult_passengers) + count($request->kid_passengers);

        $discount_rate = 0.2;
        $promo_rate = 0;

        /**
         * Seat Segment
         */
        $seat_segment = SeatSegment::where('id', $request->seat_segment_id)->with('schedule')->first();

        // Validate cut off time
        // $cutOffTimeValidator = new ValidateCutOffTime();
        // $cutOffResult = $cutOffTimeValidator->checkIfPastCutOffTime($seat_segment);

        // if ($cutOffResult instanceof JsonResponse) {
        //     return $cutOffResult;
        // }
        // $total_cost = $seat_segment['rate'] * $total_passengers;

        $total_cost = 0;

        if ($request->round_trip) {
            $seat_segment2 = SeatSegment::where('id', $request->seat_segment_id2)->with('schedule')->first();
            // $total_cost = $total_cost + ($seat_segment2['rate'] * $total_passengers);
        }

        // return $total_cost;

        /**
         * Create Passenger Record
         */

        $passengers = array_merge($request->adult_passengers, $request->kid_passengers, $request->infant_passengers);

        $passengerValidator = new ValidateIfPassengerExists();
        $validationResult = $passengerValidator->checkIfPassengersExist($request->seat_segment_id, $passengers, $connection);

        if ($validationResult instanceof JsonResponse) {
            return $validationResult;
        }

        $groupReferenceNumber = Ticket::generateGroupReferenceNumber();

        foreach ($passengers as $passenger) {

            $type = 'adult';

            if ($passenger['age'] >= 2 && $passenger['age'] <= 11) {
                $type = 'kid';
            }

            if ($passenger['age'] >= 0 && $passenger['age'] <= 1) {
                $type = 'infant';
            }

            $available_seat = $seat_segment->allocated - $seat_segment->used;

            if ($available_seat <= 0) {
                $connection->rollBack();
                return response()->json(['error' => 'TRIP_FULLY_BOOKED', 'message' => 'Trip is fully booked.'], 400);
            }

            $newPassenger = Passenger::create([
                'trip_number' => $seat_segment['trip_number'],
                // 'booking_reference_number' => $newBooking['reference_number'],
                // 'guest_reference_number' => $guest['reference_number'],
                'first_name' => strtoupper($passenger['first_name']),
                'last_name' => strtoupper($passenger['last_name']),
                'age' => $passenger['age'],
                'nationality' => $passenger['nationality'] ?? null,
                'type' => $type,
                'address' => $request->address
            ]);

            /**
             * Create Trip Record
             */

            $trip_seat_numbers = Trip::where('trip_number', $seat_segment['trip_number'])
                ->whereIn('status', ['boarded', 'checked_in', 'pending'])
                ->pluck('seat_number')->toArray();

            // Seat number
            $seat = Seat::whereNotIn('number', array_filter($trip_seat_numbers, 'strlen'))
                ->where('status', 'active')
                ->whereNotIn('auto_check_in_status', ['restricted', 'vip'])
                ->orderBy('order', 'asc')
                ->first();

            if ($request->seat_segment_id2) {
                $newPassenger2 = Passenger::create([
                    'trip_number' => $seat_segment2['trip_number'],
                    // 'booking_reference_number' => $newBooking['reference_number'],
                    // 'guest_reference_number' => $guest['reference_number'],
                    'first_name' => strtoupper($passenger['first_name']),
                    'last_name' => strtoupper($passenger['last_name']),
                    'age' => $passenger['age'],
                    'nationality' => $passenger['nationality'] ?? null,
                    'type' => $type,
                    'address' => $request->address
                ]);

                /**
                 * Create Trip Record
                 */

                $trip_seat_numbers2 = Trip::where('trip_number', $seat_segment2['trip_number'])
                    ->whereIn('status', ['boarded', 'checked_in', 'pending'])
                    ->pluck('seat_number')->toArray();

                // Seat number
                $seat2 = Seat::whereNotIn('number', array_filter($trip_seat_numbers2, 'strlen'))
                    ->where('status', 'active')
                    ->whereNotIn('auto_check_in_status', ['restricted', 'vip'])
                    ->orderBy('order', 'asc')
                    ->first();

                if (!$seat2) {
                    $connection->rollBack();
                    return response()->json(['error' => 'NO_MORE_SEAT_AVAILABLE', 'message' => 'No more seat available'], 400);
                }
            }

            if (!$seat) {
                $connection->rollBack();
                return response()->json(['error' => 'NO_MORE_SEAT_AVAILABLE', 'message' => 'No more seat available'], 400);
            }

            /**
             * Increment used allocation on segment
             */


            if ($type != 'infant') {
                SeatSegment::where('id', $seat_segment['id'])
                    ->increment('used');

                if ($request->seat_segment_id2) {
                    SeatSegment::where('id', $seat_segment2['id'])
                        ->increment('used');
                }
            }

            $buy1take1_promo = 0.5;
            $promo_discount = $seat_segment['rate'] * $buy1take1_promo;
            $senior_discount = $seat_segment['rate'] * $discount_rate;
            $discount_id = isset($passenger['discount_id']) ? $passenger['discount_id'] : null;
            $total_discount = 0;
            if ($discount_id !== null) {
                if ($discount_id === 'Buy1Take1') {
                    $discount_id = 'Promo-' . $discount_id;
                    $total_discount = $promo_discount;
                } else {
                    $prefix = $passenger['age'] >= 60 ? 'SC-' : 'PWD-';
                    $discount_id = $prefix . $discount_id;
                    $total_discount = $senior_discount;
                }
            }





            /**
             * Create Tickets
             */
            $newTicket = Ticket::create([
                'reference_number' => Ticket::generateReferenceNumber(),
                'group_reference_number' => $groupReferenceNumber,
                'trip_number' => $seat_segment['trip_number'],
                'trip_type' => $request->round_trip ? 'roundtrip' : 'oneway',
                'passenger_id' => $newPassenger['id'],
                // 'ticket_type' => isset($passenger['discount_id']) ? 'DISCOUNTED' : 'REGULAR',
                'ticket_type' => 'Regular',
                'promo_type' => '',
                'amount' => $type !== 'infant' ? $seat_segment['rate'] : 0,
                'discount' => $total_discount,
                'discount_id' => $discount_id,
                // for no promo
                // 'discount' => isset($passenger['discount_id']) ? $seat_segment['rate'] * $discount_rate : null,
                // 'discount_id' => isset($passenger['discount_id']) ? $passenger['discount_id'] : null,
                'paid_at' => null,
                'payment_reference_number' => null,
                'mode_of_payment' => null,
                'payment_status' => null,
                'payment_provider' => null,
                'payment_channel' => null,
                'payment_provider_reference_number' => null,
                'voided_by' => null,
                'voided_at' => null,
                'refunded_by' => null,
                'refunded_at' => null,
                'remarks' => null,
                'status' => 'pending',
                'contact_number' => $request->contact_number,
                'email' => $request->email,
            ]);


            if ($type !== 'infant') {

                if (isset($passenger['discount_id'])) {
                    $total_cost = $total_cost + ($seat_segment['rate'] - ($seat_segment['rate'] * $discount_rate));
                } else {
                    $total_cost = $total_cost + ($seat_segment['rate']);
                }
            }


            // Create trip
            Trip::create([
                'trip_number' => $seat_segment['trip_number'],
                'ticket_reference_number' => $newTicket['reference_number'],
                // 'guest_reference_number' => $guest['reference_number'],
                // 'booking_reference_number' => $newBooking['reference_number'],
                'passenger_id' => $newPassenger->id,
                'seat_number' => ($type != 'infant') ? $seat->number : null,
                'status' => 'pending',
                'seat_segment_id' => $seat_segment['id'],
                'printed' => 0,
                'last_printed_at' => null,
                'checked_in_at' => Carbon::now(),
                'boarded_at' => null,
                'cancelled_at' => null,
                'no_show_at' => null,
            ]);

            if ($request->seat_segment_id2) {

                /**
                 * Create Tickets for trip 2
                 */
                $newTicket2 = Ticket::create([
                    'reference_number' => Ticket::generateReferenceNumber(),
                    'group_reference_number' => $groupReferenceNumber,
                    'trip_number' => $seat_segment2['trip_number'],
                    'trip_type' => $request->round_trip ? 'roundtrip' : 'oneway',
                    'passenger_id' => $newPassenger2['id'],
                    'ticket_type' => isset($passenger['discount_id']) ? 'DISCOUNTED' : 'REGULAR',
                    'promo_type' => '',
                    'amount' => $type !== 'infant' ? $seat_segment2['rate'] : 0,
                    'discount' => isset($passenger['discount_id']) ? $seat_segment2['rate'] * $discount_rate : null,
                    'discount_id' => isset($passenger['discount_id']) ? $passenger['discount_id'] : null,
                    'paid_at' => null,
                    'payment_reference_number' => null,
                    'mode_of_payment' => null,
                    'payment_status' => null,
                    'payment_provider' => null,
                    'payment_channel' => null,
                    'payment_provider_reference_number' => null,
                    'voided_by' => null,
                    'voided_at' => null,
                    'refunded_by' => null,
                    'refunded_at' => null,
                    'remarks' => null,
                    'status' => 'pending',
                    'contact_number' => $request->contact_number,
                    'email' => $request->email,
                ]);

                if ($type !== 'infant') {
                    if (isset($passenger['discount_id'])) {
                        $total_cost = $total_cost + ($seat_segment2['rate'] - ($seat_segment2['rate'] * $discount_rate));
                    } else {
                        $total_cost = $total_cost + ($seat_segment2['rate']);
                    }
                }

                // Create trip
                Trip::create([
                    'trip_number' => $seat_segment2['trip_number'],
                    'ticket_reference_number' => $newTicket2['reference_number'],
                    // 'guest_reference_number' => $guest['reference_number'],
                    // 'booking_reference_number' => $newBooking['reference_number'],
                    'passenger_id' => $newPassenger2->id,
                    'seat_number' => ($type != 'infant') ? $seat2->number : null,
                    'status' => 'pending',
                    'seat_segment_id' => $seat_segment2['id'],
                    'printed' => 0,
                    'last_printed_at' => null,
                    'checked_in_at' => Carbon::now(),
                    'boarded_at' => null,
                    'cancelled_at' => null,
                    'no_show_at' => null,
                ]);
            }
        }

        if ($total_cost === 0) {
            return response()->json(['error' => 'invalid total cost'], 400);
        }


        $tickets = Ticket::where('group_reference_number', $groupReferenceNumber)
            ->with('passenger')
            ->with('schedule.route.origin')
            ->with('schedule.route.destination')
            ->with('schedule.transportation')
            ->with('trip')
            ->get();


        $connection->commit();
        // Mail::to($request->email)
        //     ->send(new PendingBooking($groupReferenceNumber,$tickets));
        // return [
        //     'status' => "OK",
        //     "page" => env('ONEBITS_URL') . '/book/payment/response?success=1&ref=' . $groupReferenceNumber,
        //     'reference_number' => $groupReferenceNumber
        // ];


        if($request->pickPayment=== 1){
            $userName = $request->adult_passengers[0]['first_name'] . " " . $request->adult_passengers[0]['last_name'];

            $total_ticket_cost = number_format((float)$total_cost, 2, '.', '');

            $signature = hash('sha256', env('IPAY88_MERCHANT_KEY') . env('IPAY88_MERCHANT_CODE') . $groupReferenceNumber . str_replace(array('.', ','), '', $total_ticket_cost) . 'PHP');

            // Mail::to($request->email)
            //         // ->cc($additional_emails)
            //         ->send(new NewBooking());

            return [
                'status' => "OK",
                'page' => "<html><head></head><body>DO NOT CLOSE. Redirecting to Payment Gateway.<form id='autosubmit' method='POST' name='ePayment' action='" . env('IPAY88_URL') . "' enctype='application/x-www-form-urlencoded'><input type='hidden' name='MerchantCode' value='" . env('IPAY88_MERCHANT_CODE') . "' /><input type='hidden' name='PaymentId' value='" . $request->payment_option . "' /><input type='hidden' name='RefNo' value='" . $groupReferenceNumber . "' /><input type='hidden' name='Amount' value='" . $total_ticket_cost . "' /><input type='hidden' name='Currency' value='PHP' /><input type='hidden' name='ProdDesc' value='1BITS Ferry Ticket' /><input type='hidden' name='UserName' value='" . $userName . "' /><input type='hidden' name='UserEmail' value='" . $request->email . "' /><input type='hidden' name='UserContact' value='" . $request->contact_number . "' /><input type='hidden' name='Remark' value='-' /><input type='hidden' name='SignatureType' value='SHA256' /><input type='hidden' name='Signature' value='" . htmlspecialchars($signature) . "' /><input type='hidden' name='ResponseURL' value='" . env('APP_URL') . "/api/one-bits/payment/response' /><input type='hidden' name='BackendURL' value='" . env('APP_URL') . "/api/one-bits/payment/backend' /><script type='text/javascript'>document.getElementById('autosubmit').submit();</script></body></html>",
            ];

        }else{
            Mail::to($request->email)
            ->send(new PendingBooking($groupReferenceNumber,$tickets));
        return [
            'status' => "OK",
            "page" => env('ONEBITS_URL') . '/book/payment/response?success=1&ref=' . $groupReferenceNumber,
            'reference_number' => $groupReferenceNumber
        ];
        }


        return 'OK';
        // $userName = $request->adult_passengers[0]['first_name'] . " " . $request->adult_passengers[0]['last_name'];

        // $total_promo = $total_passengers % 2 === 0 ? $total_cost / 2 : $total_cost / $total_passengers;
        // $total_ticket = $total_cost - $total_promo;
        // $sub_total = number_format((float)$total_cost,2,'.','');
        // $total_ticket_cost = number_format((float) $total_ticket, 2, '.', '');
        // $discount = isset($passenger['discount_id']) ? $seat_segment['rate'] * $discount_rate : 0;
        // $total_discount = number_format((float)$discount, 2, '.', '');
        // $total_promo = number_format((float)$total_promo,2,'.','');

        // //maya integration
        // //check if paymaya gateway is maintanance or not
        // if (env('BOOKING_PAYMENT_GATEWAY_PAYMAYA_OPEN') == false) return response()->json(['message' => 'PayMaya Payment Gateway is closed.'], 500);


        // $public_key = base64_encode(env('MAYA_PUBLIC_API_KEY') . ':');

        // $data = [
        //     "totalAmount" => [
        //         "value" => $total_ticket_cost,
        //         "currency" => "PHP",
        //         "details" => [
        //             "discount" => $total_discount,
        //             "promo" => $total_promo,
        //             "serviceCharge" => 0,
        //             "shippingFee" => 0,
        //             "tax" => 0,
        //             "subtotal" => $sub_total,
        //         ]
        //     ],
        //     "buyer" => [
        //         "firstName" =>  $request->adult_passengers[0]['first_name'],
        //         "middleName" => '',
        //         "lastName" => $request->adult_passengers[0]['last_name'],
        //         "contact" => [
        //             "phone" => $request->contact_number,
        //             "email" => $request->email
        //         ],
        //         "shippingAddress" => [
        //             "firstName" => $request->adult_passengers[0]['first_name'],
        //             "middleName" => '',
        //             "lastName" => $request->adult_passengers[0]['last_name'],
        //             "phone" => $request->contact_number,
        //             "email" => $request->email,
        //             "countryCode" => "PH",
        //             "shippingType" => "ST" // ST - for standard, SD - for same day
        //         ],
        //         "billingAddress" => [
        //             "line1" => "",
        //             "line2" => "",
        //             "city" => "",
        //             "state" => "",
        //             "zipCode" => "",
        //             "countryCode" => "PH",
        //         ]
        //     ],
        //     "items" => [
        //         [
        //             "name" => '1BITS Ferry Ticket',
        //             "quantity" => $total_passengers,
        //             "code" =>  $groupReferenceNumber,
        //             // Add client number in description and discounts
        //             "amount" => [
        //                 "value" => $sub_total,
        //                 "details" => [
        //                     "discount" => $total_discount,
        //                     "promo" => $total_promo,
        //                     "serviceCharge" => 0,
        //                     "shippingFee" => 0,
        //                     "tax" => 0,
        //                     "subtotal" => $sub_total,
        //                 ]
        //             ],
        //             "totalAmount" => [
        //                 "value" => $sub_total,
        //                 "details" => [
        //                     "discount" => $total_discount,
        //                     "promo" => $total_promo,
        //                     "serviceCharge" => 0,
        //                     "shippingFee" => 0,
        //                     "tax" => 0,
        //                     "subtotal" => $sub_total,
        //                 ]
        //             ]
        //         ]
        //     ],
        //     "redirectUrl" => [
        //         "success" => env('APP_URL') . '/one-bits/payment-successful?reference_number=' . $groupReferenceNumber,
        //         "failure" => env('APP_URL') . '/one-bits/payment-failed?reference_number=' . $groupReferenceNumber,
        //         "cancel" => env('APP_URL') . '/one-bits/payment-cancel?reference_number=' . $groupReferenceNumber,
        //     ],
        //     "requestReferenceNumber" => $groupReferenceNumber,
        //     "metadata" => [
        //         'client_number' => $request->contact_number,
        //         "transaction_id" => $groupReferenceNumber,
        //         'pay_to' => "1Bataan Integrated Transport System Inc",
        //         "application" => "OneBits"
        //     ]
        // ];

        // $response = Http::withHeaders([
        //     'Content-Type' => 'application/json',
        //     'Authorization' => 'Basic ' . $public_key,
        // ])->post(env('MAYA_API_ENDPOINT'), $data);

        // $json_response = $response->json();

        // if ($response->ok()) {
        //     Ticket::where('group_reference_number', $groupReferenceNumber)->update([
        //         'payment_reference_number' => $json_response['checkoutId'],
        //         'mode_of_payment' => 'online_payment',
        //     ]);

        //     return [
        //         'status' => "OK",
        //         "page" => $json_response['redirectUrl']
        //     ];
        // }
        return 'OK';


        // Mail::to($request->email)
        //     // ->cc($additional_emails)
        //     ->send(new NewBooking());

    }
}
