<?php

use Illuminate\Support\Facades\Route;

// DELETE THIS FOR TESTING ONLY
use App\Mail\AgentCreated;
use Illuminate\Support\Facades\Mail;
// use Illuminate\Support\Str;

use Illuminate\Http\Request;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Models\Booking\Customer;
use App\Models\RealEstate\SalesTeam;
use App\Models\RealEstate\SalesTeamMember;
use App\Models\Transportation\Schedule;
use App\Models\Transportation\Trip;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomType;
use App\User;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/payment/{provider}/status',['as'=>'payment.status','uses'=>'PaymentController@getPayPalPaymentStatus']);
Route::get('/payment/{provider}/cancel',['as'=>'payment.cancel','uses'=>'PaymentController@cancelPayment']);

// Paymaya webhook
Route::post('/paymaya/webhookCallback/{status}', 'RealEstate\OnlinePayment@payMayaWebhookCallback');
Route::get('/paymaya/deletePayMayaWebhooks/{id}', 'RealEstate\OnlinePayment@deletePayMayaWebhooks');
Route::get('/verify_payment/{transaction_id}', 'RealEstate\OnlinePayment@verify_payment');

Route::post('/paymaya/booking/webhook/{status}', 'Booking\PaymentRequest@paymayaWebhook');

Route::get('public/payment_status/{booking_reference_number}', 'Booking\PaymentRequest@payment_status');
Route::get('public/booking_payment_check/{booking_reference_number}', 'Booking\PaymentRequest@booking_payment_check');

Route::get('/activate/{email}', 'LoginController@activateAccount');

Route::get('/viberbot/activate', 'ViberBotController@activateBot');
Route::get('/viberbot/deactivate', 'ViberBotController@deactivateBot');

// START DRAGONPAY REAL ESTATE PAYMENTS
Route::post('/pay-online/{gateway}', ['as'=>'onlinePayment.makePayment','uses'=> 'RealEstate\OnlinePayment@makePayment']);
Route::get('/pay-online-return/{gateway}', 'RealEstate\OnlinePayment@paymentReturn');
Route::post('/pay-online-postback/{gateway}', 'RealEstate\OnlinePayment@paymentPostback');
// END DRAGONPAY REAL ESTATE PAYMENTS

// Pesopay
Route::get('/pesopay/success/{transaction_id}', 'RealEstate\OnlinePayment@pesoPaySuccess');
Route::post('/pesopay/datafeed', 'RealEstate\OnlinePayment@pesoPayDataFeed');

//one bits paymaya gateway
// Route::get("/one-bits/payment-successful","OneBITS\Payment\PaymentResponse\PaymayaSuccess");
// Route::get("/one-bits/payment-failed","OneBITS\Payment\PaymentResponse\PaymayaFailed");
// Route::get("/one-bits/payment-cancel","OneBITS\Payment\PaymentResponse\PaymayaCancel");

// Guest Information Sheet
Route::get('/hotel/guest-registration-form/{booking_reference_number}', 'Booking\GuestInformationSheetView');

// 12.12 promo payment page

// Route::get('/12.12-promo-online-payment', function() {
//     return view('promo-online-payment');
// });

Route::get('/auto-cancel-vouchers', '/ing\AutoCancelVoucher@__invoke');


Route::get('/PaidVoucher/{transaction_reference_number?}', function ($transaction_reference_number = 'transaction_reference_number') {

    $paid_vouchers = \App\Models\Booking\GeneratedVoucher::with('voucher')->with('customer')->where('transaction_reference_number', $transaction_reference_number)->get();

    return PDF::loadView('pdf.booking.voucher_confirmation', ['paid_vouchers' => $paid_vouchers, 'transaction_reference_number' => $transaction_reference_number ])->stream();

});

// GO-NUA2CE
// GD-MVRFXF
// GO-CFKI6Y

// EMAIL CONFIRMATION - PENDING / CONFIRMED
Route::get('/test_confirmation', function () {

    $booking = \App\Models\Booking\Booking::where('reference_number', 'DT-UEERSI')
                    ->with('bookedBy')
                    ->with('customer')
                    ->with(['guests' => function ($q) {
                        $q->with('tee_time.schedule');
                        $q->with('guestTags');
                        $q->with(['tripBookings.schedule' => function ($q) {
                            $q->with('transportation');
                            $q->with('route.origin');
                            $q->with('route.destination');
                        }]);

                    }])
                    ->with('inclusions.packageInclusions')
                    ->with('inclusions.guestInclusion')
                    ->with('invoices')
                    ->with('room_reservations.room_type')
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

    return \PDF::loadView('pdf.booking.booking_confirmation', ['booking' => $booking, 'camaya_transportations' => $camaya_transportations])->stream();
});

// SCAN PLACEHOLDER
Route::get('/scan-successful/{pass_id}', 'AutoGate\ViewScanSuccessful');

Route::get('/get-passengers-report/{trip_number}', function ($trip_number) {

    $status = [
                'pending',
                'checked_in',
                'boarded',
                'no_show',
                'cancelled',
                'arriving',
            ];

     return Excel::download(
             new \App\Exports\FerryPassengersTransportationManifest2($trip_number, $status), $trip_number.' - Trip-Passengers-Report.xlsx'
      );

});

// TEST REVENUE REPORT
Route::get('/get-revenue', function () {

    $real_estate_tags = [
        "ESLCC - Sales Agent",
        "ESLCC - Sales Client",
        "ESLCC- Sales Agent",
        "SDMB - Sales Director Marketing Budget",
        "Thru Agent - Paying",
        "Walk-in - Sales Client",
        "Walk-in - Sales Agent",
        "GOLF - RE",
        "RE - Golf",
        "RE - Tripping"
    ];

    $bpo_tags = [
        "Commercial",
        "Commercial-Promo",
        "Commercial - Promo",
        "Commercial - Promo (Luventure)",
        "Commercial - PROMO",
        "Commercial - Promo (Camaya Summer)",
        "Commercial - Promo (Save Now, Travel Later)",
        "Commercial - Promo 12.12",
        "Commercial - Promo (12.12)",
        "Commercial (Admin)",
        "Commercial - Admin",
        "Commercial - Corre",
        "Commercial (Website)",
        "Commercial - Website",
        "Commercial - Walk-in",
        "Commercial - Golf",
        "Corporate FIT",
        "Corporate Sales",
        "CVoucher",
        "DTT WALK-IN",
        "DTT Walk-in",
        "DTT - Walk-in",
        "Paying - Walk-in",
        "OTA - KLOOK",
        "OTA - Klook",
        "Walk-in - Commercial",
        "Walk-in - DTT",
        "Walk-in - Paying",
        "GOLF - Commercial",

        "1Bataan ITS - Employee",
        "DEV 1",
        "DEV1 - Employee",
        "ESLCC - Employee",
        "ESLCC - Employee / Guest",
        "ESLCC - Events/Guests",
        "ESLCC - Event/Guest",
        "ESLCC - Employee",
        "ESTVC - EMP",
        "ESTVC - Employee",
        "People Plus - Employee",
        "Orion Sky - Employee",
        "SLA - Employee",

        "House Use",
        "DEV1 - Events/Guests",
        "DEV1 - Event/Guest",
        "ESLCC - GC",
        "ESLCC - Guest",
        "ESLCC -GUEST",
        "ESLCC FOC",
        "ESLCC - FOC",
        "ESLCC GUEST",
        "ESLCC- EVENTS/ GUESTS",
        "ESLCC-EVENTS/ GUESTS",
        "ESLCC-Events/Guests",
        "ESLCC - Event/Guest",
        "ESLCC-GUEST",
        "ESTLC-Guest",
        "ESTLC - Guest",
        "ESTLC - Event/Guest",
        "ESTVC - GC",
        "ESTVC - Events/Guests",
        "ESTVC-GUEST",
        "ESTVC-Guest/Events",
        "ESTVC -Guest",
        "ESTVC - Guest",
        "Magic Leaf - Event/Guest",
        "SLA - Events/Guests",
        "SLA - Event/Guest",
        "TA-Rates",
        "TA - Rates",
        "VIP Guest",
        "Orion Sky",
        "Orion Sky - Guest",
        "Golf Member",
    ];

    $start_date = \Carbon\Carbon::parse('2022-10-05')->setTimezone('Asia/Manila')->format('Y-m-d');
    $end_date = \Carbon\Carbon::parse('2022-10-09')->setTimezone('Asia/Manila')->format('Y-m-d');

    $inclusions = \App\Models\Booking\Inclusion::join('bookings', 'bookings.reference_number', '=', 'inclusions.booking_reference_number')
                                                ->select(
                                                    'inclusions.booking_reference_number',
                                                    'inclusions.item',
                                                    'inclusions.quantity',
                                                    'inclusions.selling_price',
                                                    'inclusions.walkin_price',
                                                    \DB::raw("DATE_FORMAT(bookings.start_datetime, '%W') as day"),
                                                    'bookings.adult_pax',
                                                    'bookings.kid_pax'
                                                )
                                                ->with(['booking' => function ($q) {
                                                    $q->select('reference_number', 'id');
                                                    $q->with('tags');
                                                }])
                                                ->where('bookings.start_datetime', '>=', $start_date)
                                                ->where('bookings.start_datetime', '<=', $end_date)
                                                ->where('bookings.status', 'confirmed')
                                                ->whereIn('inclusions.type', ['product', 'package'])
                                                ->take(100)
                                                ->get();

    $promos = collect($inclusions)->pluck('item')->unique()->values()->all();

    $data = [];

    foreach ($promos as $item) {
        $data[] = [
            'promo' => $item,
            'brn' => [],
            'bpo' => [
                'wd' => [
                    'pax' => 0,
                    'sales' => 0,
                ],
                'we' => [
                    'pax' => 0,
                    'sales' => 0,
                ],
            ],
            're' => [
                'wd' => [
                    'pax' => 0,
                    'sales' => 0,
                ],
                'we' => [
                    'pax' => 0,
                    'sales' => 0,
                ],
            ],
        ];
    }

    // $booking_reference_numbers = [];

    foreach ($inclusions as $inclusion) {

        $index = collect($data)->search( function($item) use ($inclusion) {
                return $inclusion['item'] == $item['promo'];
        });

        $real_estate_tag_matches = false;
        $bpo_tag_matches = false;

        // $data[$index]['re']['wd']['pax'] = $data[$index]['re']['wd']['pax'] + ($inclusion['adult_pax'] + $inclusion['kid_pax']);

       $tags = collect($inclusion['booking']['tags']->pluck('name')->all());

        if ($tags) {
            foreach ($tags as $tag) {
                if (in_array($tag, $real_estate_tags)) {
                    $real_estate_tag_matches = true;
                } else if (in_array($tag, $bpo_tags)) {
                    $bpo_tag_matches = true;
                }
            }

            if ($real_estate_tag_matches) {
                //
                if (in_array($inclusion['day'], ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])) {
                    $data[$index]['re']['wd']['sales'] = $data[$index]['re']['wd']['sales'] + ($inclusion['selling_price'] * $inclusion['quantity']);

                    //pax
                    if (! in_array($inclusion['booking_reference_number'], $data[$index]['brn'])) {
                        $data[$index]['re']['wd']['pax'] = $data[$index]['re']['wd']['pax'] + ($inclusion['adult_pax'] + $inclusion['kid_pax']);
                        $data[$index]['brn'][] = $inclusion['booking_reference_number'];
                    }
                } else {
                    $data[$index]['re']['we']['sales'] = $data[$index]['re']['we']['sales'] + ($inclusion['selling_price'] * $inclusion['quantity']);

                    //pax
                    if (! in_array($inclusion['booking_reference_number'], $data[$index]['brn'])) {
                        $data[$index]['re']['we']['pax'] = $data[$index]['re']['we']['pax'] + ($inclusion['adult_pax'] + $inclusion['kid_pax']);
                        $data[$index]['brn'][] = $inclusion['booking_reference_number'];
                    }
                }

            } else if ($bpo_tag_matches) {
                if (in_array($inclusion['day'], ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])) {
                    $data[$index]['bpo']['wd']['sales'] = $data[$index]['bpo']['wd']['sales'] + ($inclusion['selling_price'] * $inclusion['quantity']);

                    //pax
                    if (! in_array($inclusion['booking_reference_number'], $data[$index]['brn'])) {
                        $data[$index]['bpo']['wd']['pax'] = $data[$index]['bpo']['wd']['pax'] + ($inclusion['adult_pax'] + $inclusion['kid_pax']);
                        $data[$index]['brn'][] = $inclusion['booking_reference_number'];
                    }
                } else {
                    $data[$index]['bpo']['we']['sales'] = $data[$index]['bpo']['we']['sales'] + ($inclusion['selling_price'] * $inclusion['quantity']);

                    //pax
                    if (! in_array($inclusion['booking_reference_number'], $data[$index]['brn'])) {
                        $data[$index]['bpo']['we']['pax'] = $data[$index]['bpo']['we']['pax'] + ($inclusion['adult_pax'] + $inclusion['kid_pax']);
                        $data[$index]['brn'][] = $inclusion['booking_reference_number'];
                    }
                }
            }
        }
    }

    return $data;
});

// TEST REVENUE PER MOP REPORT
Route::get('/get-revenue-mop', function () {

    $start_date = \Carbon\Carbon::parse('2022-10-07')->setTimezone('Asia/Manila')->format('Y-m-d');
    $end_date = \Carbon\Carbon::parse('2022-10-07')->setTimezone('Asia/Manila')->format('Y-m-d');

    $inclusions = \App\Models\Booking\Inclusion::join('bookings', 'bookings.reference_number', '=', 'inclusions.booking_reference_number')
                                                ->select(
                                                    'inclusions.booking_reference_number',
                                                    'inclusions.item',
                                                    'inclusions.quantity',
                                                    'inclusions.selling_price',
                                                    'inclusions.walkin_price',
                                                )
                                                ->with(['booking' => function ($q) {
                                                    $q->select('reference_number', 'id');
                                                    $q->with('booking_payments');
                                                }])
                                                ->where('bookings.start_datetime', '>=', $start_date)
                                                ->where('bookings.start_datetime', '<=', $end_date)
                                                ->whereIn('bookings.status', ['confirmed'])
                                                ->whereIn('inclusions.type', ['product', 'package'])
                                                ->get();

    $promos = collect($inclusions)->pluck('item')->unique()->values()->all();

    $data = [];

    foreach ($promos as $item) {
        $data[] = [
            'promo' => $item,
            'payment_providers' => [
                'maya' => 0,
                'paypal' => 0,
                'bank_transfer' => 0,
                'bank_deposit' => 0,
                'cash' => 0,
            ],
        ];
    }

    foreach ($inclusions as $inclusion) {

        $index = collect($data)->search( function($item) use ($inclusion) {
                return $inclusion['item'] == $item['promo'];
        });

        if ($inclusion) {
            $data[$index]['payment_providers']['cash'] =  $data[$index]['payment_providers']['cash']  + (collect($inclusion['booking']['booking_payments'])->where('mode_of_payment', 'cash')->sum('amount'));

            $data[$index]['payment_providers']['bank_deposit'] =  $data[$index]['payment_providers']['bank_deposit']  + (collect($inclusion['booking']['booking_payments'])->where('mode_of_payment', 'bank_deposit')->sum('amount'));

            $data[$index]['payment_providers']['bank_transfer'] =  $data[$index]['payment_providers']['bank_transfer']  + (collect($inclusion['booking']['booking_payments'])->where('mode_of_payment', 'bank_transfer')->sum('amount'));

            $data[$index]['payment_providers']['paypal'] += (collect($inclusion['booking']['booking_payments'])->where('provider', 'paypal')->sum('amount'));

            $data[$index]['payment_providers']['maya'] += (collect($inclusion['booking']['booking_payments'])->where('provider', 'paymaya')->sum('amount'));
        }

    }

    return $data;

});

// TEST SDMB BOOKING CONSUMPTION
Route::get('/sdmb-booking-consumption', function () {

    $data = [];
    $sales_director_ids = [];

    $start_date = \Carbon\Carbon::parse('2022-10-05')->setTimezone('Asia/Manila')->format('Y-m-d');
    $end_date = \Carbon\Carbon::parse('2022-10-13')->setTimezone('Asia/Manila')->format('Y-m-d');

    $sales_teams = SalesTeam::with(['owner.user'])->get();

    foreach($sales_teams as $k=>$sales_team) {

        $data[] = [
            'sd_id' => $sales_team->owner->user->id,
            'sd_name' => $sales_team->owner->user->first_name . ' ' . $sales_team->owner->user->last_name,
            'ferry_total_sales' => 0,
            'land_total_sales' => 0,
        ];

        $sales_director_ids[] = $sales_team->owner->user->id;
    }

    $bookings = \App\Models\Booking\Booking::whereIn('sales_director_id', $sales_director_ids)
                                            ->where('bookings.start_datetime', '>=', $start_date)
                                            ->where('bookings.start_datetime', '<=', $end_date)
                                            ->where('bookings.status', 'confirmed')
                                            ->select('reference_number', 'sales_director_id', 'mode_of_transportation')
                                            ->withCount(['invoices as invoices_grand_total' => function ($q) {
                                                $q->select(\DB::raw('sum(grand_total)'));
                                            }])
                                            ->get();

        foreach($bookings as $k=>$booking) {

            $sd = collect($data)->firstWhere('sd_id', $booking['sales_director_id']);

            $index = collect($data)->search( function($item) use ($booking) {
                return $booking['sales_director_id'] == $item['sd_id'];
            });

            if ($booking['mode_of_transportation'] == 'camaya_transportation') $data[$index]['ferry_total_sales'] += $booking['invoices_grand_total'];

            if ($booking['mode_of_transportation'] != 'camaya_transportation') $data[$index]['land_total_sales'] += $booking['invoices_grand_total'];
        }

        return response()->json([
            'status' => true,
            'data' => array_values($data),
            'ids' => $bookings
        ]);
});

// TEST SDMB SALES ROOM ACCOM
Route::get('/sdmb-sales-room', function () {

    // $data = [];
    // $sales_director_ids = [];

    $start_date = \Carbon\Carbon::parse('2022-10-21')->setTimezone('Asia/Manila')->format('Y-m-d');
    $end_date = \Carbon\Carbon::parse('2022-10-23')->setTimezone('Asia/Manila')->format('Y-m-d');


    $bookings = \App\Models\Booking\Booking::whereBetween('bookings.start_datetime', [$start_date, $end_date])
                                            ->where('bookings.status', 'confirmed')
                                            ->where('bookings.type', 'ON')
                                            ->select('reference_number', 'sales_director_id', 'agent_id', 'customer_id', 'start_datetime', 'end_datetime', 'remarks')
                                            ->with(['customer', 'room_reservations_no_filter.room_type.property', 'sales_director', 'agent'])
                                            ->with(['inclusions' => function ($q) {
                                                $q->select('booking_reference_number', 'id', 'code', 'type', 'quantity', 'price');
                                                $q->where('code','EXTRAPAX');
                                                $q->orWhere('type', 'room_reservation');
                                            }])
                                            ->whereHas('tags', function ($q) {
                                                $q->select('booking_id');
                                                $q->where('name','SDMB - Sales Director Marketing Budget');
                                            })
                                            ->withCount(['inclusions as inclusions_grand_total' => function ($q) {
                                                $q->whereNull('deleted_at');
                                                $q->where('type', 'room_reservation');
                                                $q->select(\DB::raw('sum(price)'));
                                            }])
                                            ->withCount(['inclusions as inclusions_grand_total_on_package' => function ($q) {
                                                $q->whereNull('deleted_at');
                                                // $q->where('code','EXTRAPAX');
                                                $q->where('type', 'room_reservation');
                                                $q->whereNotNull('parent_id');
                                                $q->select(\DB::raw('sum(original_price)'));
                                            }])
                                            ->get();

        foreach ($bookings as $booking) {
            $booking['hotel'] = '';
            $booking['room_type'] = [];
            $booking['no_of_rooms'] = count($booking->room_reservations_no_filter);
            $booking['check_in'] = $booking->start_datetime;
            $booking['check_out'] = $booking->end_datetime;
            $booking['no_of_nights'] = \Carbon\Carbon::parse($booking->start_datetime)->diffInDays(\Carbon\Carbon::parse($booking->end_datetime));

            foreach($booking->room_reservations_no_filter as $room_reservation) {
                $booking['hotel'] = $room_reservation->room_type->property->code;
                $room_allocation = RoomAllocation::find($room_reservation->allocation_used);
            }
        };

        return $bookings;
});

// CHECK BOOKINGS ON CAMAYA TRANSPORT WITHOUT PASSES
Route::get('/check-bookings-camaya-transport', function () {
    return Booking::where('mode_of_transportation', 'camaya_transportation')
                 ->where('start_datetime', '>=', now())
                 ->whereNotIn('status', ['cancelled'])
                 ->doesntHave('camaya_transportation')
                 // ->get();
                 ->pluck('reference_number');
 });

 //
 Route::get('/allocation-v-manifest', function () {

    $trips = Trip::join('schedules', 'schedules.trip_number', '=', 'trips.trip_number')
                ->join('seat_segments', 'seat_segments.id', '=', 'trips.seat_segment_id')
                ->join('guests', 'guests.reference_number', '=', 'trips.guest_reference_number')
                ->where('schedules.trip_date', '>=', now())
                ->where('guests.type', '!=', 'infant')
                ->whereNotIn('trips.status', ['cancelled', 'no_show'])
                ->selectRaw('trips.seat_segment_id, seat_segments.name, schedules.trip_date, trips.trip_number, COUNT(trips.id) as total')
                ->groupBy('trips.seat_segment_id', 'trips.trip_number', 'seat_segments.name', 'schedules.trip_date')
                ->get();

    $allocations = \App\Models\Transportation\SeatSegment::whereIn('id', collect($trips)->pluck('seat_segment_id')->all())->select('id', 'name', 'allocated', 'used')->get();

    $data = [];

    foreach ($trips as $trip) {
        foreach ($allocations as $allocation) {
            if ($trip['seat_segment_id'] == $allocation['id'] && $trip['total'] != $allocation['used']) {
                $data[] = [
                    'seat_segment_id' => $allocation['id'],
                    'trip_date' => $allocation['trip_date'],
                    'name' => $trip['name'],
                    'trip_number' => $trip['trip_number'],
                    'total' => $trip['total'],
                    'allocated' => $allocation['allocated'],
                    'used' => $allocation['used']
                ];
            }
        }
    }

    return $data;
});

Route::get('/check-bookings-without-maingate-passes', function () {

    $bookings = \App\Models\Booking\Booking::where('start_datetime', '>=', now()->format('Y-m-d'))
            ->whereDoesntHave('guests.passes', function ($q) {
                $q->where('type', 'Main Gate Access');
            })
            ->whereDoesntHave('tags', function ($q) {
                $q->where('name', 'Ferry Only');
            })
            ->whereIn('status', ['confirmed', 'pending'])
            ->with('bookedBy:id,first_name,last_name')
            ->get();

    echo "<table border='1' cellpadding='4' cellspacing='0'>";

    echo "<tr><th colspan='9'>Bookings without Main gate passes<br/>Total: ".count($bookings)."</th></tr>";
    echo "<tr><th>#</th><th>Booking Ref. No</th><th>Status</th><th>Date of arrival</th><th>Date of departure</th><th>Adult pax</th><th>Kid pax</th><th>Infant pax</th><th>Booked by</th></tr>";

    foreach ($bookings as $key => $booking) {
        echo "<tr><td>".($key + 1)."</td><td>".$booking['reference_number']."</td><td>".$booking['status']."</td><td>".$booking['start_datetime']."</td><td>".$booking['end_datetime']."</td><td>".$booking['adult_pax']."</td><td>".$booking['kid_pax']."</td><td>".$booking['infant_pax']."</td><td>".($booking['bookedBy']['first_name'] ?? '')." ".($booking['bookedBy']['last_name'] ?? '')."</td></tr>";
    }

    echo "</table>";

    return "OK";

});

Route::get('/count-admin-bookings', function () {

    $users = User::where('user_type', 'admin')
                    // ->select('id', 'email', 'first_name', 'last_name')
                    ->pluck('id');

    $bookings = \App\Models\Booking\Booking::whereIn('created_by', $users)
                        ->whereIn('status', ['confirmed', 'pending'])
                        ->groupBy(\DB::raw('DATE_FORMAT(`created_at`, "%Y")'))
                        ->groupBy('created_by')
                        ->with('bookedBy:id,first_name,last_name')
                        ->selectRaw('COUNT(*) as total, created_by, DATE_FORMAT(`created_at`, "%Y") as year')
                        // ->orderBy('total', 'DESC')
                        ->orderBy('year', 'DESC')
                        ->get();

    echo "<table border='1' cellpadding='4' cellspacing='0'>";

    echo "<tr><th colspan='9'>Admin users bookings count<br/>Total: ".count($bookings)."</th></tr>";
    echo "<tr><th>#</th><th>First name</th><th>Last name</th><th>Year</th><th>Count</th></tr>";

    foreach ($bookings as $key => $user) {
        echo "<tr><td>".($key + 1)."</td><td>".$user['bookedBy']['first_name']."</td><td>".$user['bookedBy']['last_name']."</td><td>".$user['year']."</td><td>".$user['total']."</td></tr>";
    }

    echo "</table>";

    return "END";

});

Route::get('/count-bookings-per-segment', function () {

    $admins = User::whereIn('user_type', ['admin'])
                    ->pluck('id');

    $agents = User::whereIn('user_type', ['agent'])
                    ->pluck('id');

    $admin_bookings = \App\Models\Booking\Booking::whereIn('created_by', $admins)
                        ->whereIn('status', ['confirmed', 'pending'])
                        ->groupBy(\DB::raw('DATE_FORMAT(`created_at`, "%Y")'))
                        ->selectRaw('COUNT(*) as total, DATE_FORMAT(`created_at`, "%Y") as year')
                        ->orderBy('year', 'DESC')
                        ->get();

    $agent_bookings = \App\Models\Booking\Booking::whereIn('created_by', $agents)
                        ->whereIn('status', ['confirmed', 'pending'])
                        ->groupBy(\DB::raw('DATE_FORMAT(`created_at`, "%Y")'))
                        ->selectRaw('COUNT(*) as total, DATE_FORMAT(`created_at`, "%Y") as year')
                        ->orderBy('year', 'DESC')
                        ->get();

    $commercial_bookings = \App\Models\Booking\Booking::whereIn('status', ['confirmed', 'pending'])
                        ->whereNull('created_by')
                        ->groupBy(\DB::raw('DATE_FORMAT(`created_at`, "%Y")'))
                        ->selectRaw('COUNT(*) as total, DATE_FORMAT(`created_at`, "%Y") as year')
                        ->orderBy('year', 'DESC')
                        ->get();

    // return [$admin_bookings, $agent_bookings, $commercial_bookings];
    echo "<table border='1' cellpadding='4' cellspacing='0'>";

    echo "<tr><th colspan='3'>Bookings count per segment</th></tr>";

    echo "<tr><th colspan='3'>Admin</th></tr>";
    echo "<tr><th>Type</th><th>Year</th><th>Total</th></tr>";
    foreach ($admin_bookings as $key => $item) {
        echo "<tr><td>Admin</td><td>".$item['year']."</td><td>".$item['total']."</td></tr>";
    }

    echo "<tr><th colspan='3'>Agent</th></tr>";
    echo "<tr><th>Type</th><th>Year</th><th>Total</th></tr>";
    foreach ($agent_bookings as $key => $item) {
        echo "<tr><td>Agent</td><td>".$item['year']."</td><td>".$item['total']."</td></tr>";
    }

    echo "<tr><th colspan='3'>Commercial</th></tr>";
    echo "<tr><th>Type</th><th>Year</th><th>Total</th></tr>";
    foreach ($commercial_bookings as $key => $item) {
        echo "<tr><td>Commercial</td><td>".$item['year']."</td><td>".$item['total']."</td></tr>";
    }

    echo "</table>";

    return "END";

});

Route::get('/hotel-occupancy-per-segment/{start?}', function ($start_date_param = 'today') {

    // $start_date = $request->start_date;

    $is_date = DateTime::createFromFormat('Y-m-d', $start_date_param);


    $start_date_param = $is_date ? $start_date_param : now()->format('Y-m-d');
    $end_date_param = $is_date ? \Carbon\Carbon::parse($start_date_param)->addDays(6)->format('Y-m-d') : now()->addDays(6)->format('Y-m-d');

    $start_date = $start_date_param." 12:00:00";
    $end_date = $end_date_param." 11:00:00";

    $period = \Carbon\CarbonPeriod::create($start_date_param, $end_date_param);

    // $dates = [];

    // return $dates;


    // 30% - commercial
    // 40% - RE / SDMB
    // 10% - Others (employees / executives)

    // Real Estate tags
    $real_estate_tags = [
        "ESLCC - Sales Agent",
        "ESLCC - Sales Client",
        "ESLCC- Sales Agent",
        "SDMB - Sales Director Marketing Budget",
        "Thru Agent - Paying",
        "Walk-in - Sales Client",
        "Walk-in - Sales Agent",
        "GOLF - RE",
        "RE - Golf",
        "RE - Tripping"
    ];

    // Home Owner tags
    $homeowner_tags = [
        "ESLCC - AFV",
        "ESLCC - CSV",
        "ESLCC - HOA",
        "ESLCC HOA",
        "HOA",
        "HOA - (Paying-promo)",
        "HOA - Paying Promo",
        "HOA - AF Unit Owner",
        "HOA - Voucher",
        "HOA – Gate Access",
        "HOA ACCESS STUB",
        "HOA - Access Stub",
        "HOA CLIENT",
        "HOA - Client",
        "HOA MEMBER",
        "HOA - Member",
        "HOA - Walk-in",
        "HOA - Golf",
        "GOLF - HOA",
        "HOA - Sales Director Marketing Budget",
        "Property Owner (Non-Member)",
        "Property Owner (HOA Member)",
        "Property Owner (Dependents)",
        "Property Owner (Guests)",
    ];

    // Commercial tags
    $commercial_tags = [
        "Commercial",
        "Commercial-Promo",
        "Commercial - Promo",
        "Commercial - Promo (Luventure)",
        "Commercial - PROMO",
        "Commercial - Promo (Camaya Summer)",
        "Commercial - Promo (Save Now, Travel Later)",
        "Commercial - Promo 12.12",
        "Commercial - Promo (12.12)",
        "Commercial (Admin)",
        "Commercial - Admin",
        "Commercial - Corre",
        "Commercial (Website)",
        "Commercial - Website",
        "Commercial - Walk-in",
        "Commercial - Golf",
        "Corporate FIT",
        "Corporate Sales",
        "CVoucher",
        "DTT WALK-IN",
        "DTT Walk-in",
        "DTT - Walk-in",
        "Paying - Walk-in",
        "OTA - KLOOK",
        "OTA - Klook",
        "Walk-in - Commercial",
        "Walk-in - DTT",
        "Walk-in - Paying",
        "GOLF - Commercial",
    ];

    // Employees tags
    $employees_tags = [
        "1Bataan ITS - Employee",
        "DEV 1",
        "DEV 1 - Employee",
        "DEV1 - Employee",
        "ESLCC - Employee",
        "ESLCC - Employee/Guest",
        "ESLCC - Employee / Guest",
        "ESLCC - Events/Guests",
        "ESLCC - Event/Guest",
        "ESLCC - Employee",
        "ESTLC - Employee",
        "ESTVC - EMP",
        "ESTVC - Employee",
        "People Plus - Employee",
        "Orion Sky - Employee",
        "SLA - Employee",
        "DS18 - Employee",
        "DS18 - Events Guest",
    ];

    // $others_tags = [
    //     "House Use",
    //     "DEV1 - Events/Guests",
    //     "DEV1 - Event/Guest",
    //     "DEV 1 - Event/Guest",
    //     "ESLCC - GC",
    //     "ESLCC - Guest",
    //     "ESLCC -GUEST",
    //     "ESLCC FOC",
    //     "ESLCC - FOC",
    //     "ESLCC GUEST",
    //     "ESLCC- EVENTS/ GUESTS",
    //     "ESLCC-EVENTS/ GUESTS",
    //     "ESLCC-Events/Guests",
    //     "ESLCC - Event/Guest",
    //     "ESLCC-GUEST",
    //     "ESTLC-Guest",
    //     "ESTLC - Guest",
    //     "ESTLC - Event/Guest",
    //     "ESTVC - GC",
    //     "ESTVC - Events/Guests",
    //     "ESTVC-GUEST",
    //     "ESTVC-Guest/Events",
    //     "ESTVC -Guest",
    //     "ESTVC - Guest",x
    //     "Magic Leaf - Event/Guest",
    //     "SLA - Events/Guests",
    //     "SLA - Event/Guest",
    //     "TA-Rates",
    //     "TA - Rates",
    //     "VIP Guest",
    //     "Orion Sky",
    //     "Orion Sky - Guest",
    //     "Golf Member",
    //     "Camaya Golf Voucher",
    // ];

    $af_rooms_count = \App\Models\Hotel\Room::whereHas("property", function ($q) {
        $q->where('code', 'AF');
    })->count();

    $sands_rooms_count = \App\Models\Hotel\Room::whereHas("property", function ($q) {
        $q->where('code', 'SANDS');
    })->count();

    $room_reservations = \App\Models\Hotel\RoomReservation::where(function ($query) use ($start_date, $end_date) {
                $query->where(function ($query) use ($start_date, $end_date) {
                    $query->where('room_reservations.start_datetime', '<=', $start_date)
                        ->where('room_reservations.end_datetime', '>=', $start_date);
                })->orWhere(function ($query) use ($start_date, $end_date) {
                    $query->where('room_reservations.start_datetime', '<=', $end_date)
                        ->where('room_reservations.end_datetime', '>=', $end_date);
                })->orWhere(function ($query) use ($start_date, $end_date) {
                    $query->where('room_reservations.start_datetime', '>=', $start_date)
                        ->where('room_reservations.end_datetime', '<=', $end_date);
                });
            })
        ->whereIn('room_reservations.status', ['confirmed', 'pending', 'blackout', 'checked_in', 'checked_out'])
        // ->whereNotIn('status', ['blackout'])
        ->with('booking.tags:booking_id,name')
        ->with('booking:id,reference_number')
        ->with('room.property:id,code')
        // ->whereIn('property_id', [1])
        ->select('booking_reference_number', 'room_id', 'start_datetime', 'end_datetime')
        ->get();

    $data = [];

    // return $rooms;

    foreach ($room_reservations as $room_reservation) {

        $booking_tags = collect($room_reservation['booking']['tags'] ?? [])->pluck('name')->all();

        $real_estate_intersects = array_intersect($booking_tags, $real_estate_tags);
        $homeowner_intersects = array_intersect($booking_tags, $homeowner_tags);
        $commercial_intersects = array_intersect($booking_tags, $commercial_tags);
        $employees_intersects = array_intersect($booking_tags, $employees_tags);

        $tagging = 'other';

        if (count($real_estate_intersects) > 0) $tagging = 're';
        if (count($homeowner_intersects) > 0) $tagging = 'hoa';
        if (count($commercial_intersects) > 0) $tagging = 'comm';
        if (count($employees_intersects) > 0) $tagging = 'emp';

        $reservation_start = \Carbon\Carbon::parse($room_reservation['start_datetime'])->setTimezone('Asia/Manila')->format('Y-m-d');
        $reservation_end = \Carbon\Carbon::parse($room_reservation['end_datetime'])->subDays(1)->setTimezone('Asia/Manila')->format('Y-m-d');
        $reservation_period = \Carbon\CarbonPeriod::create($reservation_start, $reservation_end);

        $data[] = [
            // 'room_number' => $room['number'],
            'property' => $room_reservation['room']['property']['code'],
            // 'is_booked' => count($room['room_reservations']) ? 'YES' : 'NO',
            // 'booking_tags' => $booking_tags,
            // 'start_date' => \Carbon\Carbon::parse($room_reservation['start_datetime'])->setTimezone('Asia/Manila')->format('Y-m-d'),
            'occupancy_dates' => collect($reservation_period)->map( function ($i) { return $i->format('Y-m-d'); })->toArray(),
            // 'end_date' => DateTime::createFromFormat('Y-m-d', $room_reservation['booking']['end_datetime']) ?? null,
            'tagging' => $tagging
        ];

    }

    $total_count = [
        're' => 0,
        'hoa' => 0,
        'comm' => 0,
        'emp' => 0,
        'other' => 0,
    ];


    $data2 = [];

    foreach ($period as $date) {


        $af_rm_res = collect($data)->filter(function ($value, $key) use ($date) {
            return in_array($date->format('Y-m-d'), $value['occupancy_dates']) && $value['property'] == 'AF';
        });

        $sands_rm_res = collect($data)->filter(function ($value, $key) use ($date) {
            return in_array($date->format('Y-m-d'), $value['occupancy_dates']) && $value['property'] == 'SANDS';
        });

        $af_segments = collect($af_rm_res)->countBy('tagging');
        $sands_segments = collect($sands_rm_res)->countBy('tagging');

        $data2[] = [
            'date' => $date->format('Y-m-d'),
            'af' => [
                'total_reserved' => collect($af_segments)->sum(),
                'segments' => $af_segments,
                'occupancy' => round(collect($af_segments)->sum() / $af_rooms_count * 100, 2),
                'total_rooms' => $af_rooms_count

            ],
            'sands' => [
                'total_reserved' => collect($sands_segments)->sum(),
                'segments' => $sands_segments,
                'occupancy' => round(collect($sands_segments)->sum() / $sands_rooms_count * 100, 2),
                'total_rooms' => $sands_rooms_count

            ],
        ];

    }

    // return $data2;

    echo "<table border='1' cellpadding='4' cellspacing='0'>";

    echo "<tr><th colspan='17'>Hotel Occupancy Per Segment ".$start_date_param." to ". $end_date_param ."</th></tr>";


    echo "<tr><th></th><th colspan='8'>AF</th><th colspan='8'>SANDS</th></tr>";
    echo "<tr><th>Date</th><th>Total Rooms</th><th>Total Reserved</th><th>Commercial</th><th>RE</th><th>HOA</th><th>Employee</th><th>Other</th><th>Occupancy</th><th>Total Rooms</th><th>Total Reserved</th><th>Commercial</th><th>RE</th><th>HOA</th><th>Employee</th><th>Other</th><th>Occupancy</th></tr>";
    foreach ($data2 as $key => $item) {
        echo "<tr><td>".$item['date']."</td>
            <td>".$item['af']['total_rooms']."</td>
            <td>".$item['af']['total_reserved']."</td>
            <td>".(isset($item['af']['segments']['comm']) ? round($item['af']['segments']['comm'] / $item['af']['total_rooms'] * 100, 2) : 0)."</td>
            <td>".(isset($item['af']['segments']['re']) ? round($item['af']['segments']['re'] / $item['af']['total_rooms'] * 100, 2) : 0)."</td>
            <td>".(isset($item['af']['segments']['hoa']) ? round($item['af']['segments']['hoa'] / $item['af']['total_rooms'] * 100, 2) : 0)."</td>
            <td>".(isset($item['af']['segments']['emp']) ? round($item['af']['segments']['emp'] / $item['af']['total_rooms'] * 100, 2) : 0)."</td>
            <td>".(isset($item['af']['segments']['other']) ? round($item['af']['segments']['other'] / $item['af']['total_rooms'] * 100, 2) : 0)."</td>
            <td>".$item['af']['occupancy']."</td>

            <td>".$item['sands']['total_rooms']."</td>
            <td>".$item['sands']['total_reserved']."</td>
            <td>".$item['sands']['occupancy']."</td>
            <td>".(isset($item['sands']['segments']['comm']) ? round($item['sands']['segments']['comm'] / $item['sands']['total_rooms'] * 100, 2) : 0)."</td>
            <td>".(isset($item['sands']['segments']['re']) ? round($item['sands']['segments']['re'] / $item['sands']['total_rooms'] * 100, 2) : 0)."</td>
            <td>".(isset($item['sands']['segments']['hoa']) ? round($item['sands']['segments']['hoa'] / $item['sands']['total_rooms'] * 100, 2) : 0)."</td>
            <td>".(isset($item['sands']['segments']['emp']) ? round($item['sands']['segments']['emp'] / $item['sands']['total_rooms'] * 100, 2) : 0)."</td>
            <td>".(isset($item['sands']['segments']['other']) ? round($item['sands']['segments']['other'] / $item['sands']['total_rooms'] * 100, 2) : 0)."</td>
        </tr>";
    }

    echo "</table>";

    return "END";

});

// Route::get('/hotel-occupancy/{start_date?}', function ($start_date_param = 'today') {

//     $is_date = DateTime::createFromFormat('Y-m-d', $start_date_param);


//     $start_date_param = $is_date ? $start_date_param : now()->format('Y-m-d');
//     $end_date_param = $is_date ? \Carbon\Carbon::parse($start_date_param)->addDays(30)->format('Y-m-d') : now()->addDays(30)->format('Y-m-d');

//     $start_date = $start_date_param." 12:00:00";
//     $end_date = $end_date_param." 11:00:00";

//     /**
//      * Create period based on date range
//      */
//     $period = \Carbon\CarbonPeriod::create($start_date_param, $end_date_param);

//     $room_reservations = \App\Models\Hotel\RoomReservation::where(function ($query) use ($start_date, $end_date) {
//         $query->where(function ($query) use ($start_date, $end_date) {
//             $query->where('room_reservations.start_datetime', '<=', $start_date)
//                 ->where('room_reservations.end_datetime', '>=', $start_date);
//         })->orWhere(function ($query) use ($start_date, $end_date) {
//             $query->where('room_reservations.start_datetime', '<=', $end_date)
//                 ->where('room_reservations.end_datetime', '>=', $end_date);
//         })->orWhere(function ($query) use ($start_date, $end_date) {
//             $query->where('room_reservations.start_datetime', '>=', $start_date)
//                 ->where('room_reservations.end_datetime', '<=', $end_date);
//         });
//     })
//     ->whereIn('room_reservations.status', ['confirmed', 'pending', 'blackout', 'checked_in', 'checked_out'])
//     // ->whereNotIn('status', ['blackout'])
//     // ->whereHas('booking', function ($q) {
//     //     $q->whereNotIn('status', ['cancelled']);
//     // })
//     ->with(['booking' => function ($q) {
//         $q->select('id','reference_number','bookings.status');
//         $q->with('tags:booking_id,name');
//     }])
//     ->with('room.property:id,code')
//     ->with('room.type:id,code')
//     // ->whereIn('property_id', [1])
//     ->select('booking_reference_number', 'room_id', 'start_datetime', 'end_datetime', 'category', 'status')
//     ->get();

//     $data = collect(collect(collect($room_reservations)
//                 ->map( function ($item, $key) {
//                     $reservation_start = \Carbon\Carbon::parse($item['start_datetime'])->setTimezone('Asia/Manila')->format('Y-m-d');
//                     $reservation_end = \Carbon\Carbon::parse($item['end_datetime'])->subDays(1)->setTimezone('Asia/Manila')->format('Y-m-d');
//                     $reservation_period = \Carbon\CarbonPeriod::create($reservation_start, $reservation_end);
//                     $occupancy_dates = collect($reservation_period)->map( function ($i) { return $i->format('Y-m-d'); })->toArray();

//                     return [
//                         // 'booking_ref' => $item['booking_reference_number'],
//                         // 'status' => $item['status'],
//                         'category' => $item['category'],
//                         'occupancy_dates' => $occupancy_dates,
//                         'room_type' => $item['room']['type']['code'],
//                         'property_code' => $item['room']['property']['code'],
//                         'tags' => isset($item['booking']['tags']) ? collect($item['booking']['tags'])->pluck("name")->all() : []
//                     ];
//                 })
//                 ->values()
//                 ->all())
//                 ->mapToGroups( function ($item, $key) {
//                     return [$item['room_type'] => $item['occupancy_dates']];
//                 })
//                 ->toArray())
//                 ->map( function ($item, $key) {
//                     return collect($item)->collapse()->countBy()->all();
//                 })->all();

//     // foreach ($period as $date) {
//     //     $data[] = [
//     //         // Date
//     //         'date' => $date->format('Y-m-d'),
//     //         // Count of reservations
//     //         'reservations' => collect(collect($room_reservations)
//     //                                 ->map( function ($item, $key) {
//     //                                     $reservation_start = \Carbon\Carbon::parse($item['start_datetime'])->setTimezone('Asia/Manila')->format('Y-m-d');
//     //                                     $reservation_end = \Carbon\Carbon::parse($item['end_datetime'])->subDays(1)->setTimezone('Asia/Manila')->format('Y-m-d');
//     //                                     $reservation_period = \Carbon\CarbonPeriod::create($reservation_start, $reservation_end);
//     //                                     $occupancy_dates = collect($reservation_period)->map( function ($i) { return $i->format('Y-m-d'); })->toArray();

//     //                                     return [
//     //                                         'occupancy_dates' => $occupancy_dates,
//     //                                         'room_type' => $item['room']['type']['code'],
//     //                                         'property_code' => $item['room']['property']['code'],
//     //                                         'tags' => collect($item['booking']['tags'])->pluck("name")->all()
//     //                                     ];
//     //                                 })
//     //                                 // ->filter( function ($value) use ($date) {
//     //                                 //     return in_array($date->format('Y-m-d'), $value['occupancy_dates']);
//     //                                 // })
//     //                                 ->values()
//     //                                 ->all())
//     //                                 ->mapToGroups( function ($item, $key) {
//     //                                     return [$item['room_type'] => $item['occupancy_dates']];
//     //                                 })
//     //                                 ->toArray()
//     //     ];
//     // }

//     return $data;
// });

Route::get('/bookings-payment-refund', function () {

    $bookings = Booking::where('start_datetime', '>=', '2023-04-13')
            ->whereIn('status', ['confirmed', 'pending'])
            ->where( function ($q) {
                $q->whereIn('mode_of_payment', ['paypal', 'paymaya', 'bank_deposit']);
                $q->orWhere('mode_of_payment', null);
            })
            ->where('mode_of_transportation', 'camaya_transportation')
            ->with(['booking_payments' => function ($q) {

            }])
            ->with('customer')
            ->with(['trips' => function($q) {
                // $q->with('seatSegments');
                $q->join('seat_segments', 'trips.seat_segment_id', '=', 'seat_segments.id');
                $q->select('trips.seat_segment_id', 'trips.booking_reference_number', 'seat_segments.name');
                $q->whereNotIn('trips.status', ['cancelled']);
            }])
            // ->select('bookings.reference_number', 'mode_of_transportation', 'mode_of_payment')
            ->addSelect(['*',
                'balance' => \App\Models\Booking\Invoice::select(\DB::raw('sum(balance) as total_balance'))
                                    ->whereNotIn('status', ['void'])
                                    ->whereColumn('booking_reference_number', 'bookings.reference_number')
                                    ->limit(1),
                'grand_total' => \App\Models\Booking\Invoice::select(\DB::raw('sum(grand_total) as grand_total'))
                                    ->whereNotIn('status', ['void'])
                                    ->whereColumn('booking_reference_number', 'bookings.reference_number')
                                    ->limit(1)
            ])
            // ->limit(10)
            ->orderBy('start_datetime', 'ASC')
            ->get();

    // return $bookings;

    echo "<table border='1' cellpadding='1' cellspacing='0'>";

    // echo "<tr><th colspan='17'>Hotel Occupancy Per Segment ".$start_date_param." to ". $end_date_param ."</th></tr>";

    echo "<tr>
            <th>#</th>
            <th>Start Date</th>
            <th>Ref #</th>
            <th>Portal</th>
            <th>Transpo</th>
            <th>Balance</th>
            <th>Grand total</th>
            <th>Email</th>
            <th>Contact</th>
            <th># of Payment</th>
            <th>Payments</th>
            <th>Original Payment mode</th>
            <th>Ferry Segment</th>
        </tr>";
    foreach ($bookings as $key => $item) {
        echo "<tr>
            <td>".($key+1)."</td>
            <td>".\Carbon\Carbon::parse($item['start_datetime'])->format('Y-m-d')."</td>
            <td>".$item['reference_number']."</td>
            <td>".$item['portal']."</td>
            <td>".$item['mode_of_transportation']."</td>
            <td>".$item['balance']."</td>
            <td>".$item['grand_total']."</td>
            <td>".$item['customer']['email']."</td>
            <td>".$item['customer']['contact_number']."</td>
            <td>".count($item['booking_payments'])."</td>
            <td>".implode(", ", collect($item['booking_payments'])->map( function ($i) {
                return "(".$i['mode_of_payment']."-".$i['provider'].", ".$i['payment_reference_number'].")";
            })->all())."</td>
            <td>".$item['mode_of_payment']."</td>
            <td>".implode(", ", collect($item['trips'])->map( function ($i) {
                return "(".$i['name'].")";
            })->unique()->values()->all())."</td>
        </tr>";
    }

    echo "</table>";
    return 'END';

});

// Route::get('/sales-team-update', function () {

//     // return User::where('user_type', 'agent')->where('id', 197)->first();

//     // 167 with Parent team
//     // 197 no parent team

//     $user = User::where('users.id', 172)
//                 ->select('users.*')
//                 ->parentTeam()
//                 ->subTeam()
//                 ->first();

//     return $user;

//     return 'OK';
// });

Route::get('/hash/{string}', function ($string) {

    echo "string :". $string;
    echo "<br/><br/>hash() :". hash('sha256', $string);
    // echo "<br/>sha1() :". sha256($string);
});

Route::view('/{path?}', 'index')->where('path', '.*');
// Route::view('/{path?}', 'index');
