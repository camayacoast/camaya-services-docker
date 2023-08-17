<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Booking\CreateBookingAsGuestRequest;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\Booking\Booking;
use App\Models\Booking\Guest;
use App\Models\Booking\GuestVehicle;
use App\Models\Booking\AdditionalEmail;
use App\Models\Booking\BookingTag;
use App\Models\Booking\Product;
use App\Models\Booking\Inclusion;
use App\Models\Booking\Invoice;
use App\Models\Booking\Package;
use App\Models\Booking\Customer;
use App\Models\Booking\ProductPass;
use App\Models\Booking\Stub;
use App\Models\Booking\Setting;
use App\Models\Booking\DailyGuestLimit;

use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomType;
use App\Models\Hotel\RoomRate;
use App\Models\Hotel\RoomAllocation;

use App\Models\Transportation\Schedule;
use App\Models\Transportation\SeatSegment;
use App\Models\Transportation\Trip;
use App\Models\Transportation\Seat;
use App\Models\Transportation\Passenger;

use App\Models\AutoGate\Pass;

use App\Models\Golf\TeeTimeSchedule;
use App\Models\Golf\TeeTimeGuestSchedule;

use App\Mail\Booking\NewBooking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CreateBookingAsGuest extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreateBookingAsGuestRequest $request)
    {
        //
        // return $request->all();

        ///////// BEGIN TRANSACTION //////////
        $connection = DB::connection('camaya_booking_db');
        $connection->beginTransaction();
        $booking_error_count = 0;

        $arrival_date = Carbon::parse(date('Y-m-d', strtotime($request->date_of_visit[0])))->setTimezone('Asia/Manila');
        $departure_date = Carbon::parse(date('Y-m-d', strtotime($request->date_of_visit[1])))->setTimezone('Asia/Manila');

        // Multiple in nights
        $nights = $arrival_date->diffInDays($departure_date);

        $period = CarbonPeriod::create($arrival_date, $departure_date);
        
        $array_period_dates=[];
        
        foreach ($period as $date_period) {
            if ($date_period->format('Y-m-d') != $departure_date->format('Y-m-d')) {
                $array_period_dates[] = $date_period->format('Y-m-d');
            }
        }

        /**
         * Set booking type
         */
        $booking_type = 'DT';
        $booking_type2 = 'DT';

        if ($request->isGolf) {
            $booking_type = 'GD';
        }

        if ($departure_date->gt($arrival_date)) {
            $booking_type = 'ON';
            $booking_type2 = 'ON';

            if ($request->isGolf) {
                $booking_type = 'GO';
            }
        }

        // $trip_kid_max = 30;
        // $trip_infant_max = 15;

        $trip_kid_max = Setting::where('code', 'TRIP_KID_MAX')->first()->value;
        $trip_infant_max = Setting::where('code', 'TRIP_INFANT_MAX')->first()->value;
        $trip_adult_max = Setting::where('code', 'TRIP_ADULT_MAX')->first()->value;

        $infant_min_age = 0;
        $infant_max_age = 2;

        $kid_min_age = 3;
        $kid_max_age = 11;

        $adult_min_age = 12;
        $adult_max_age = 100;

        // Weekday or Weekend
        $dtt_weekday = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $dtt_weekend = ['Saturday', 'Sunday'];
        $ovn_weekday = ['Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        $ovn_weekend = ['Friday','Saturday', 'Sunday'];

        /**
         * Check if email is banned
         * $request->email
         */
        $ban_list = [
            // 'carladiomampo.eslcc@gmail.com',
            // 'email4testing2021@gmail.com',
            'edlyngrace.10@gmail.com'
        ];

        if (in_array($request->email, $ban_list)) {
            //
            Log::info($request->email." tried to book at ".now().".");
            return response()->json(['error' => 'LIMIT_REACHED', 'message' => 'Sorry, your booking limit has been reached.'], 400);
        }

        /**
         * Generate New Unique Booking Reference Number
         */ 
        $booking_reference_number = $booking_type."-".\Str::upper(\Str::random(6));

        // Creates a new reference number if it encounters duplicate
        while (Booking::where('reference_number', $booking_reference_number)->exists()) {
            $booking_reference_number = $booking_type."-".\Str::upper(\Str::random(6));
        }

        /**
         * Create new customer record
         */
        $newCustomer = Customer::firstOrCreate(
            ['email' => $request->email],
            [
            'first_name' => $request->first_name,
            // 'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'nationality' => $request->nationality ?? 'none',
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'email' => $request->email,
            'created_by' => null,
        ]);

        // Identifying age group
        $allGuests = array_merge($request->adult_guests, isset($request->kid_guests) ? $request->kid_guests : [], isset($request->infant_guests) ? $request->infant_guests : []);

        $adult_count = 0;
        $kid_count = 0;
        $infant_count = 0;

        foreach ($allGuests as $key => $guest) {
            if ($guest['age'] >= $adult_min_age) {
                $adult_count++;
            } else if ($guest['age'] >= $kid_min_age && $guest['age'] <= $kid_max_age ) {
                $kid_count++;
            } else if ($guest['age'] >= $infant_min_age && $guest['age'] <= $infant_max_age ) {
                $infant_count++;
            }
        }

        // $daily_limit = 1400; // Setting::where('code', 'COMMERCIAL_DAILY_LIMIT')->first()->value ?? 1400;
        $daily_limit = Setting::where('code', 'COMMERCIAL_DAILY_LIMIT')->first()->value ?? 1400;

        $main_gate_pass = Stub::where('type', 'Main Gate Access')->first();

        /**
         * Check if pax can still fit the daily limit
         * */ 
        // Get pax booked today
        //-- Active guests

        $guest_count = Guest::join('bookings', 'guests.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($arrival_date) {
                    
                $q->where('bookings.start_datetime', '=', $arrival_date);
                // $q->orWhere('bookings.start_datetime', '<=', $arrival_date)
                //     ->where('bookings.end_datetime', '>=', $arrival_date)
                //     ->where('bookings.end_datetime', '!=', $arrival_date);
                $q->whereDoesntHave('booking.tags', function ($q) {
                    $q->where('name', 'Ferry Only');
                });
            })
            ->where('bookings.portal', 'website')
            ->whereIn('bookings.status', ['confirmed', 'pending'])
            ->whereIn('guests.status', ['arriving', 'on_premise', 'checked_in'])
            ->whereNull('guests.deleted_at')
            ->where('guests.type','!=','infant')
            ->count();

        // $check_daily_limit_per_day = DailyGuestLimit::whereIn('date', count($array_period_dates) > 0 ? $array_period_dates : [$arrival_date->format('Y-m-d')])->where('category', 'Commercial')->get();
        $check_daily_limit_per_day = DailyGuestLimit::whereIn('date', [$arrival_date->format('Y-m-d')])->where('category', 'Commercial')->get();

        // [ [date: '2023-01-01', category: 'Admin', limit: 100], [date: '2023-01-02', category: 'Admin', limit: 100] ]
        /**
         * Set and Check daily limit per day
         * DGL, Daily Guest Limit
         */

        // $total_pax = $adult_count + $kid_count + $infant_count;
        $total_pax = $adult_count + $kid_count;

        $overlimit_days = [];

        foreach ($check_daily_limit_per_day as $limit_per_day) {
            $available_per_day = intval($limit_per_day['limit']) - $guest_count;
            if ($total_pax > $available_per_day) {
                $overlimit_days[] = $limit_per_day['date'];
            }
        }

        if (count($overlimit_days) > 0) {
            $connection->rollBack();
            return response()->json(['error' => 'DAILY_LIMIT_REACHED', 'message' => 'Daily limit reached for date(s) '. implode(", ", $overlimit_days) .'.'], 400);
        }

        $available = intval($daily_limit) - $guest_count;
        
        if ($total_pax > $available && !collect($check_daily_limit_per_day)->firstWhere('date', $arrival_date->format('Y-m-d'))) {
            $connection->rollBack();
            $booking_error_count++;
            return response()->json(['error' => 'DAILY_LIMIT_REACHED', 'message' => 'Daily limit reached.'], 400);
        }

        /**
         * Check infant/kid for ferry allocation
         */
        if ($request->firstTrip) { 
            // Check infant count Nov 10, 2021
            if ($infant_count > 0 || $kid_count > 0 || $adult_count > 0) {

                $first_trip = SeatSegment::where('id', $request->firstTrip['seat_segment_id'])->select('trip_number')->first();

                $passengers_age = \App\Models\Transportation\Passenger::where('passengers.trip_number', $first_trip['trip_number'])
                                        ->join('bookings', 'passengers.booking_reference_number', '=', 'bookings.reference_number')
                                        ->join('trips', 'passengers.id', '=', 'trips.passenger_id')
                                        ->whereIn('bookings.status', ['pending', 'confirmed'])
                                        ->whereIn('trips.status', ['boarded', 'checked_in', 'pending'])
                                        // ->where('passengers.age', '<=', 12)
                                        ->select('passengers.age')
                                        ->pluck('passengers.age');

                $trip_infant_count = 0;
                $trip_kid_count = 0;
                $trip_adult_count = 0;

                foreach ($passengers_age as $p) {
                    if ($p <= $infant_max_age) {
                        $trip_infant_count++;
                    }

                    if ($p >= $kid_min_age && $p <= $kid_max_age) {
                        $trip_kid_count++;
                    }

                    if ($p >= $adult_min_age && $p <= $adult_max_age) {
                        $trip_adult_count++;
                    }
                }

                $available_slot_for_infant = max($trip_infant_max - $trip_infant_count, 0);
                $available_slot_for_kid = max($trip_kid_max - $trip_kid_count, 0);
                $available_slot_for_adult = max($trip_adult_max - $trip_adult_count, 0);

                if ($infant_count > $available_slot_for_infant) {
                    $connection->rollBack();
                    // $booking_error_count++;
                    return response()->json(['error' => 'FIRST_TRIP_FULLY_BOOKED_INFANT', 'message' => 'Sorry, we reached the maximum no. of infants allowed to board on the first trip.'], 400);
                }

                if ($kid_count > $available_slot_for_kid) {
                    $connection->rollBack();
                    // $booking_error_count++;
                    return response()->json(['error' => 'FIRST_TRIP_FULLY_BOOKED_KID', 'message' => 'Sorry, we reached the maximum no. of kids allowed to board on the first trip.'], 400);
                }

                if ($adult_count > $available_slot_for_adult) {
                    $connection->rollBack();
                    // $booking_error_count++;
                    return response()->json(['error' => 'FIRST_TRIP_FULLY_BOOKED_ADULT', 'message' => 'Sorry, we reached the maximum no. of adults allowed to board on the first trip.'], 400);
                }

                if ($request->secondTrip) {

                    $second_trip = SeatSegment::where('id', $request->secondTrip['seat_segment_id'])->select('trip_number')->first();

                    $passengers_age = \App\Models\Transportation\Passenger::where('passengers.trip_number', $second_trip['trip_number'])
                                            ->join('bookings', 'passengers.booking_reference_number', '=', 'bookings.reference_number')
                                            ->join('trips', 'passengers.id', '=', 'trips.passenger_id')
                                            ->whereIn('bookings.status', ['pending', 'confirmed'])
                                            ->whereIn('trips.status', ['boarded', 'checked_in', 'pending'])
                                            // ->where('passengers.age', '<=', 12)
                                            ->select('passengers.age')
                                            ->pluck('passengers.age');

                    $trip_infant_count = 0;
                    $trip_kid_count = 0;
                    $trip_adult_count = 0;

                    foreach ($passengers_age as $p) {
                        if ($p <= $infant_max_age) {
                            $trip_infant_count++;
                        }
    
                        if ($p >= $kid_min_age && $p <= $kid_max_age) {
                            $trip_kid_count++;
                        }

                        if ($p >= $adult_min_age && $p <= $adult_max_age) {
                            $trip_adult_count++;
                        }
                    }

                    $available_slot_for_infant = max($trip_infant_max - $trip_infant_count, 0);
                    $available_slot_for_kid = max($trip_kid_max - $trip_kid_count, 0);
                    $available_slot_for_adult = max($trip_adult_max - $trip_adult_count, 0);


                    if ($infant_count > $available_slot_for_infant) {
                        $connection->rollBack();
                        // $booking_error_count++;
                        return response()->json(['error' => 'SECOND_TRIP_FULLY_BOOKED_INFANT', 'message' => 'Sorry, we reached the maximum no. of infants allowed to board on the second trip.'], 400);
                    }

                    if ($kid_count > $available_slot_for_kid) {
                        $connection->rollBack();
                        // $booking_error_count++;
                        return response()->json(['error' => 'SECOND_TRIP_FULLY_BOOKED_KID', 'message' => 'Sorry, we reached the maximum no. of kids allowed to board on the second trip.'], 400);
                    }

                    if ($adult_count > $available_slot_for_adult) {
                        $connection->rollBack();
                        // $booking_error_count++;
                        return response()->json(['error' => 'SECOND_TRIP_FULLY_BOOKED_ADULT', 'message' => 'Sorry, we reached the maximum no. of adults allowed to board on the second trip.'], 400);
                    }

                } 
            }
        }

        // if ($request->firstTrip && ($kid_count || $infant_count)) { 
        //     return response()->json(['error' => 'Kids and infant are not allowed on ferry.'], 400);
        // }

        /**
         * Create booking record
         */
        $newBooking = Booking::create([
            // 'user_id' => $request->customer->type == 'user' ? $request->customer->id : null,
            'customer_id' => $newCustomer->id,
            'reference_number' => $booking_reference_number,
            'start_datetime' => $arrival_date,
            'end_datetime' => $departure_date,
            // 'adult_pax' => $request->adult_pax,
            // 'kid_pax' => isset($request->kid_pax) ? $request->kid_pax : 0,
            // 'infant_pax' => isset($request->infant_pax) ? $request->infant_pax : 0,
            'adult_pax' => $adult_count,
            'kid_pax' => $kid_count,
            'infant_pax' => $infant_count,
            'status' => $request->asDraft ? 'draft' : 'pending',
            'rating' => 0,
            'label' => $request->label,
            'remarks' => $request->remarks,
            'type' => $booking_type2,
            'source' => $request->source,
            'mode_of_transportation' => isset($request->guest_vehicles) ? 'own_vehicle' : ($request->mode_of_transportation ?? 'undecided'),
            'eta' => $request->eta,
            'portal' => 'website',
            'mode_of_payment' => $request->modeOfPayment,
            'auto_cancel_at' => Carbon::parse(strtotime($request->auto_cancel_at))->addMinutes(60)->setTimezone('Asia/Manila'),
            'created_by' => null,
        ]);

        /**
         * Checks if booking is created
         */
        if (!$newBooking) {
            $connection->rollBack();
            $booking_error_count++;
            return response()->json(['error' => 'booking not created'], 400);
        }

        /**
         * Create guest records
         */

        $guests_to_save = [];

        foreach ($allGuests as $key => $guest) {

            /**
             * Generate New Unique Booking Reference Number
             */ 
            $guest_reference_number = "G-".\Str::upper(\Str::random(6));

            // Creates a new reference number if it encounters duplicate
            while (Guest::where('reference_number', $guest_reference_number)->exists()) {
                $guest_reference_number = "G-".\Str::upper(\Str::random(6));
            }

            /**
             * TEE TIME GOLF
             */

            if ($request->isGolf) {
                $guest_tee_time_schedule_ids = collect($request->guestTeeTime)
                                    ->where('index', $key)
                                    ->where('type', $guest['type'])
                                    ->pluck('tee_time_schedule_id')->all();

                $tee_time_schedules = TeeTimeSchedule::whereIn('id', $guest_tee_time_schedule_ids)
                                                ->withCount(['guests' => function ($query) {                                
                                                    $query->join('bookings', 'tee_time_guest_schedules.booking_reference_number', '=', 'bookings.reference_number');
                                                    $query->whereIn('bookings.status', ['pending', 'confirmed']);
                                                }])
                                                ->get();

                foreach ($tee_time_schedules as $tee_time_schedule) {
                    if (($tee_time_schedule['guests_count'] + 1) > $tee_time_schedule['allocation']) {
                        $connection->rollBack();                    
                        return response()->json(['error' => 'TEE_TIME_SCHEDULE_FULL', 'message' => 'Tee time schedule is already full.'], 400);
                    } else {
                        TeeTimeGuestSchedule::create([
                            'booking_reference_number' => $booking_reference_number,
                            'guest_reference_number' => $guest_reference_number,
                            'tee_time_schedule_id' => $tee_time_schedule['id'],
                            'status' => null,
                            'deleted_by' => null,
                            'deleted_at' => null,
                        ]);
                    }
                }
            }

            $guest_type = 'adult';
            if ($guest['age'] >= $kid_min_age && $guest['age'] <= $kid_max_age ) {
                $guest_type = 'kid';
            } else if ($guest['age'] >= $infant_min_age && $guest['age'] <= $infant_max_age ) {
                $guest_type = 'infant';
            }

            $guests_to_save[] = new Guest([
                'reference_number' => $guest_reference_number,
                'first_name' => $guest['first_name'],
                'last_name' => $guest['last_name'],
                'age' => $guest['age'],
                'nationality' => $guest['nationality'] ?? 'none',
                'type' => $guest_type,
                'status' => 'arriving',
            ]);

            /**
             * Create main gate pass
             */
            Pass::createProductPasses($main_gate_pass->id, $booking_reference_number, $guest_reference_number, $arrival_date, $departure_date, null);

            
        }

        $newBooking->guests()->saveMany($guests_to_save);

        $newBooking->refresh();

        /**
         * Guest vehicles
         */

        if (isset($request->guest_vehicles)) {
            $guest_vehicles_to_save = [];

            foreach ($request->guest_vehicles as $guest_vehicle) {

                $guest_vehicles_to_save[] = new GuestVehicle([
                    'model' => $guest_vehicle['vehicle_model'],
                    'plate_number' => $guest_vehicle['vehicle_plate_number'],
                ]);

            }
            $newBooking->guestVehicles()->saveMany($guest_vehicles_to_save);
        }

        /**
         * Add additional emails for booking
         */

        if (isset($request->additional_emails)) {
            $additional_emails_to_save = [];

            foreach ($request->additional_emails as $additional_email) {

                $additional_emails_to_save[] = new AdditionalEmail([
                    'email' => $additional_email,
                    'created_by' => null
                ]);

            }
            $newBooking->additionalEmails()->saveMany($additional_emails_to_save);
        }

        /**
         * Add tags
         */
        
            
            $tags_to_save = [ 
                new BookingTag([
                    'name' => 'Commercial (Website)',
                    'created_by' => 0])
                ];

            if (isset($request->tags)) {
                
                foreach ($request->tags as $tag) {

                    $tags_to_save[] = new BookingTag([
                        'name' => $tag,
                        'created_by' => 0
                    ]);
                }
            }
            
            $newBooking->tags()->saveMany($tags_to_save);

        

        /**
         * Get All Selected Products from Booking Form
         */
        $selectedBookingProducts = Product::whereIn('code', collect($request->selectedProducts)->pluck('code')->all())->with('productPass')->get();
        $perGuestProducts = collect($selectedBookingProducts)->where('type', 'per_guest');
        $perBookingProducts = collect($selectedBookingProducts)->where('type', 'per_booking');

        $selectedBookingPackages = Package::whereIn('code', collect($request->selectedPackages)->pluck('code')->all())->with('packageInclusions.product')->get();
        $perGuestPackages = collect($selectedBookingPackages)->where('type', 'per_guest');
        $perBookingPackages = collect($selectedBookingPackages)->where('type', 'per_booking');

        
        // return [$selectedBookingProducts, $perGuestProducts->all(), $perBookingProducts->all()];

        /**
         * Create Invoice
         */

        $newBooking->refresh();

        $invoice_total_cost = null;
        $invoice_grand_total = null;
        $invoice_balance = null;

        $generateInvoiceNumber = "C-".Str::padLeft($newBooking->id, 7, '0');

        $newBooking->invoices()->create([
            'reference_number' => $generateInvoiceNumber,
            'batch_number' => 0,
            'status' => 'draft',
            'due_datetime' => null, // In the settings page, set the default number of days until invoice due
            'paid_at' => null,
            'total_cost' => 0,
            'discount' => 0,
            'sales_tax' => 0,
            'grand_total' => 0,
            'total_payment' => 0,
            'balance' => 0,
            'change' => 0,
            'remarks' => null,
            'created_by' => null,
            'deleted_by' => null,
        ]);

        $newBooking->refresh();

        /**
         * Add per guest booking inclusions and packages
         */

        $per_guest_inclusions_to_save = [];


        foreach ($newBooking->guests as $guest) {

            foreach ($perGuestProducts->all() as $item) {

                // Check if product doesn't exceed daily limit
                // $item['quantity_per_day']
                $addons_per_product = \App\Models\Booking\Addon::where('code', $item['code'])
                                        ->where('status', 'valid')
                                        ->where('date', $arrival_date)
                                        ->whereHas('booking', function ($q) {
                                            $q->whereNotIn('status', ['cancelled']);
                                        })
                                        ->count();

                if ($guest['age'] > $infant_max_age) {
                    if ($addons_per_product >= $item['quantity_per_day'] && $item['quantity_per_day'] > 0) {

                        $connection->rollBack();
                        return response()->json(['error' => $item['code'].'_FULL', 'message' => $item['name'].' is full.'], 400);

                    }
                }

                $price = $item['price'];

                if ($guest['type'] == 'kid') $price = isset($item['kid_price']) ? $item['kid_price'] : $item['price'];
                if ($guest['age'] <= $infant_max_age) $price = isset($item['infant_price']) ? $item['infant_price'] : $item['price'];

                // Increment invoice total cost
                $invoice_total_cost = $invoice_total_cost + $price;

                $per_guest_inclusions_inclusion = $newBooking->inclusions()->create([
                    'invoice_id' => $newBooking->invoices[0]->id,
                    'guest_id' => $guest['id'],
                    'guest_reference_number' => $guest['reference_number'],
                    'item' => $item['name'],
                    'code' => $item['code'],
                    'type' => 'product',
                    'description' => $item['description'],
                    // 'serving_time' => $item['serving_time'],
                    'serving_time' => isset($item['serving_time']) ? Carbon::parse($item['serving_time'][0])->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
                    'used_at' => null,
                    'quantity' => 1,
                    'original_price' => $item['price'],
                    'price' => $price,
                    'walkin_price' => $item['walkin_price'],
                    'selling_price' => $item['price'],
                    'discount' => null,
                    'created_by' => null,
                ]);

                if ($item['quantity_per_day'] > 0 && $guest['age'] > $infant_max_age) {
                    \App\Models\Booking\Addon::create([
                        'booking_reference_number' => $newBooking['reference_number'],
                        'guest_reference_number' => $guest['reference_number'],
                        'code' => $item['code'],
                        'date' => $arrival_date,
                        'status' => 'valid',
                        // 'created_by' => $request->user()->id,
                    ]);
                }

                /**
                 * Create Passes when product has pass stub
                 */
                foreach ($item['productPass'] as $product_pass) {
                    
                    if ($main_gate_pass->id != $product_pass['stub_id']) {
                        Pass::createProductPasses($product_pass['stub_id'], $booking_reference_number, $guest['reference_number'], $arrival_date, $departure_date, $per_guest_inclusions_inclusion->id);
                    }

                }

            }

            /**
             *  Add per guest booking packages
             */
            foreach ($perGuestPackages->all() as $item) {
                
                if ($nights >= 1) {
                    for ($i = 0; $i < $nights; $i++) {

                        $per_guest_package_selling_price = $item['selling_price'];

                        $selling_price_type = null;

                        if ($item['weekday_rate'] > 0 && in_array(Carbon::parse($array_period_dates[$i])->format('l'), $ovn_weekday)) {
                            $per_guest_package_selling_price = $item['weekday_rate'];
                            $selling_price_type = 'Weekday Rate';
                        } else if ($item['weekend_rate'] > 0 && in_array(Carbon::parse($array_period_dates[$i])->format('l'), $ovn_weekend)) {
                            $per_guest_package_selling_price = $item['weekend_rate'];
                            $selling_price_type = 'Weekend Rate';
                        } else { 
                            $per_guest_package_selling_price = $item['selling_price'];
                        }

                        // Increment invoice total cost
                        $per_guest_package_selling_price = $guest['age'] <= $infant_max_age ? 0 :  $per_guest_package_selling_price;
                        $invoice_total_cost = $invoice_total_cost +  $per_guest_package_selling_price;
    
                        $per_guest_package_save = $newBooking->inclusions()->create([
                            'invoice_id' => $newBooking->invoices[0]->id,
                            'guest_id' => $guest['id'],
                            'guest_reference_number' => $guest['reference_number'],
                            'item' => $item['name'],
                            'code' => $item['code'],
                            'type' => 'package',
                            'description' => null,
                            'serving_time' => null,
                            'used_at' => null,
                            'quantity' => 1,
                            'original_price' => $guest['age'] <= $infant_max_age ? 0 : $item['regular_price'],
                            'price' => $guest['age'] <= $infant_max_age ? 0 : $per_guest_package_selling_price,
                            'walkin_price' => $guest['age'] <= $infant_max_age ? 0 : $item['walkin_price'],
                            'selling_price' => $guest['age'] <= $infant_max_age ? 0 : $per_guest_package_selling_price,
                            'selling_price_type' => $selling_price_type,
                            'discount' => null,
                            'created_by' => null,
                        ]);
    
                        $per_guest_package_to_save = [];
    
                        foreach ($item->packageInclusions as $package_inclusions) {
                            $package_inclusion_product = Product::where('code', $package_inclusions['product']['code'])->with('productPass')->first();
    
                            // Check if add exceeds daily limit
                            $addons_per_package_inclusion = \App\Models\Booking\Addon::where('code', $package_inclusion_product['code'])
                                            ->where('status', 'valid')
                                            ->where('date', $arrival_date)
                                            ->whereHas('booking', function ($q) {
                                                $q->whereNotIn('status', ['cancelled']);
                                            })
                                            ->count();
    
                            if ($addons_per_package_inclusion >= $package_inclusion_product['quantity_per_day'] && $package_inclusion_product['quantity_per_day'] > 0) {
    
                                $connection->rollBack();
                                return response()->json(['error' => $package_inclusion_product['code'].'_FULL', 'message' => $package_inclusion_product['name'].' is full.'], 400);
    
                            }
                            
                            if ($package_inclusions['product']['type'] == 'per_guest') {
                                $per_guest_package_inclusion = $newBooking->inclusions()->create([
                                    'invoice_id' => $newBooking->invoices[0]->id,
                                    'guest_id' => $guest['id'],
                                    'guest_reference_number' => $guest['reference_number'],
                                    'parent_id' => $per_guest_package_save->id,
                                    'item' => $package_inclusions['product']['name'],
                                    'code' => $package_inclusions['product']['code'],
                                    'type' => 'package_inclusion',
                                    'description' => null,
                                    'serving_time' => null,
                                    'used_at' => null,
                                    'quantity' => 1,
                                    'original_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusion_product->price,
                                    'price' => 0,
                                    'walkin_price' =>  $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['walkin_price'],
                                    'selling_price' =>  $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['price'],
                                    'discount' => null,
                                    'created_by' => null,
                                ]);
                            } else if ($package_inclusions['product']['type'] == 'per_booking') {
                                $per_guest_package_inclusion = $newBooking->inclusions()->create([
                                    'invoice_id' => $newBooking->invoices[0]->id,
                                    'guest_id' => $guest['id'],
                                    'guest_reference_number' => $guest['reference_number'],
                                    'parent_id' => $per_guest_package_save->id,
                                    'item' => $package_inclusions['product']['name'],
                                    'code' => $package_inclusions['product']['code'],
                                    'type' => 'package_inclusion',
                                    'description' => null,
                                    'serving_time' => null,
                                    'used_at' => null,
                                    'quantity' => $package_inclusions['quantity'],
                                    'original_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusion_product->price,
                                    'price' => 0,
                                    'walkin_price' =>  $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['walkin_price'],
                                    'selling_price' =>  $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['price'],
                                    'discount' => null,
                                    'created_by' => null,
                                ]);
                            }
    
                            if ($package_inclusion_product['quantity_per_day'] > 0) {
                                \App\Models\Booking\Addon::create([
                                    'booking_reference_number' => $newBooking['reference_number'],
                                    'guest_reference_number' => $guest['reference_number'],
                                    'code' => $package_inclusion_product['code'],
                                    'date' => $arrival_date,
                                    'status' => 'valid',
                                    // 'created_by' => $request->user()->id,
                                ]);
                            }
    
                            /**
                             * Create Passes when product has pass stub
                             */
                            foreach ($package_inclusion_product['productPass'] as $product_pass) {
    
                                if ($main_gate_pass->id != $product_pass['stub_id']) {
                                    Pass::createProductPasses($product_pass['stub_id'], $booking_reference_number, $guest['reference_number'], $arrival_date, $departure_date, $per_guest_package_inclusion->id);
                                }
    
                            }

                        }                 
                    }
                } else {
                    $price = $item['selling_price'];

                    $selling_price_type = null;

                    if ($item['weekday_rate'] > 0 && in_array(Carbon::parse($arrival_date)->format('l'), $dtt_weekday)) {
                        $price = $item['weekday_rate'];
                        $selling_price_type = 'Weekday Rate';
                    } else if ($item['weekend_rate'] > 0 && in_array(Carbon::parse($arrival_date)->format('l'), $dtt_weekend)) {
                        $price = $item['weekend_rate'];
                        $selling_price_type = 'Weekend Rate';
                    } else { 
                        $price = $item['selling_price'];
                    }

                    // Increment invoice total cost
                    $price = $guest['age'] <= $infant_max_age ? 0 : $price;
                    $invoice_total_cost = $invoice_total_cost + $price;

                    $per_guest_package_save = $newBooking->inclusions()->create([
                        'invoice_id' => $newBooking->invoices[0]->id,
                        'guest_id' => $guest['id'],
                        'guest_reference_number' => $guest['reference_number'],
                        'item' => $item['name'],
                        'code' => $item['code'],
                        'type' => 'package',
                        'description' => null,
                        'serving_time' => null,
                        'used_at' => null,
                        'quantity' => 1,
                        'original_price' => $guest['age'] <= $infant_max_age ? 0 : $item['regular_price'],
                        'price' => $guest['age'] <= $infant_max_age ? 0 : $price,
                        'walkin_price' => $guest['age'] <= $infant_max_age ? 0 : $item['walkin_price'],
                        'selling_price' => $guest['age'] <= $infant_max_age ? 0 : $price,
                        'selling_price_type' => $selling_price_type,
                        'discount' => null,
                        'created_by' => null,
                    ]);

                    $per_guest_package_to_save = [];

                    foreach ($item->packageInclusions as $package_inclusions) {
                        $package_inclusion_product = Product::where('code', $package_inclusions['product']['code'])->with('productPass')->first();

                        // Check if add exceeds daily limit
                        $addons_per_package_inclusion = \App\Models\Booking\Addon::where('code', $package_inclusion_product['code'])
                                        ->where('status', 'valid')
                                        ->where('date', $arrival_date)
                                        ->whereHas('booking', function ($q) {
                                            $q->whereNotIn('status', ['cancelled']);
                                        })
                                        ->count();

                        if ($addons_per_package_inclusion >= $package_inclusion_product['quantity_per_day'] && $package_inclusion_product['quantity_per_day'] > 0) {

                            $connection->rollBack();
                            return response()->json(['error' => $package_inclusion_product['code'].'_FULL', 'message' => $package_inclusion_product['name'].' is full.'], 400);

                        }
                        
                        if ($package_inclusions['product']['type'] == 'per_guest') {
                            $per_guest_package_inclusion = $newBooking->inclusions()->create([
                                'invoice_id' => $newBooking->invoices[0]->id,
                                'guest_id' => $guest['id'],
                                'guest_reference_number' => $guest['reference_number'],
                                'parent_id' => $per_guest_package_save->id,
                                'item' => $package_inclusions['product']['name'],
                                'code' => $package_inclusions['product']['code'],
                                'type' => 'package_inclusion',
                                'description' => null,
                                'serving_time' => null,
                                'used_at' => null,
                                'quantity' => 1,
                                'original_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusion_product->price,
                                'price' => 0,
                                'walkin_price' =>  $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['walkin_price'],
                                'selling_price' =>  $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['price'],
                                'discount' => null,
                                'created_by' => null,
                            ]);
                        } else if ($package_inclusions['product']['type'] == 'per_booking') {
                            $per_guest_package_inclusion = $newBooking->inclusions()->create([
                                'invoice_id' => $newBooking->invoices[0]->id,
                                'guest_id' => $guest['id'],
                                'guest_reference_number' => $guest['reference_number'],
                                'parent_id' => $per_guest_package_save->id,
                                'item' => $package_inclusions['product']['name'],
                                'code' => $package_inclusions['product']['code'],
                                'type' => 'package_inclusion',
                                'description' => null,
                                'serving_time' => null,
                                'used_at' => null,
                                'quantity' => $package_inclusions['quantity'],
                                'original_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusion_product->price,
                                'price' => 0,
                                'walkin_price' =>  $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['walkin_price'],
                                'selling_price' =>  $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['price'],
                                'discount' => null,
                                'created_by' => null,
                            ]);
                        }

                        if ($package_inclusion_product['quantity_per_day'] > 0) {
                            \App\Models\Booking\Addon::create([
                                'booking_reference_number' => $newBooking['reference_number'],
                                'guest_reference_number' => $guest['reference_number'],
                                'code' => $package_inclusion_product['code'],
                                'date' => $arrival_date,
                                'status' => 'valid',
                                // 'created_by' => $request->user()->id,
                            ]);
                        }

                        /**
                         * Create Passes when product has pass stub
                         */
                        foreach ($package_inclusion_product['productPass'] as $product_pass) {

                            if ($main_gate_pass->id != $product_pass['stub_id']) {
                                Pass::createProductPasses($product_pass['stub_id'], $booking_reference_number, $guest['reference_number'], $arrival_date, $departure_date, $per_guest_package_inclusion->id);
                            }

                        }

                    }

                }


                    $newBooking->inclusions()->saveMany($per_guest_package_to_save);

                    /**
                     *  Add package room reservations
                     */

                    $roomTypesFromPackage = collect($item['packageRoomTypeInclusions']);

                    $selectedRoomTypes = [];

                    foreach ($item['packageRoomTypeInclusions'] as $room_type) {
                        $selectedRoomTypes[] = [
                            'room_type_id' => $room_type['related_id'],
                            'quantity' => 1,
                            'entity' => $room_type['entity'],
                        ];
                    }

                    $per_booking_room_types_to_save = [];

                    // Checks if room type is selected
                    if ($selectedRoomTypes) {
                        // Check if the rooms are still available
                        // $connection->rollBack();
                        $getAvailableRooms = RoomReservation::getAvailableRooms($selectedRoomTypes, $arrival_date, $departure_date);
                        
                        $roomAvailability = collect($getAvailableRooms)->first(function ($item, $key) {
                            return ($item['total'] - $item['booked']) > 0 ;
                        });
                        
                        if ($roomAvailability) {
                        
                        // Reserve the rooms and check if they can reserve the room
                        // Loop here

                        // foreach ($selectedRoomTypes as $room_types_to_reserve) {

                            $room_type = RoomType::where('id', $roomAvailability['room_type_id'])->with('property')->first();

                            // if ($selectedPackage['quantity']) {

                                // Check if the room can accommodate the quantity
                                // $availableRooms = $roomAvailability['total'] - $roomAvailability['booked']; // DOES NOT CONSIDER BLOCKED OUT ROOMS
                                // $availableRooms = count($roomAvailability['available_room_ids']) - $roomAvailability['booked']; // WILL INCLUDE BLOCKOUT ROOMS
                                $availableRooms = count($roomAvailability['available_room_ids']);

                                if ( ($availableRooms - 1) < 0 ) {
                                    $connection->rollBack();
                                    $booking_error_count++;
                                    return response()->json(['error' => 'ROOM_FULLY_BOOKED_1', 'message' => 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name], 400);
                                    // return 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name;
                                }


                                // Loop days period room
                                for ($i = 0; $i < 1; $i++) {

                                    $room_reservation_start_datetime = Carbon::parse(date('Y-m-d', strtotime($request->date_of_visit[0])))->setTimezone('Asia/Manila');
                                    $room_reservation_start_datetime->hour = 12;
                                    $room_reservation_start_datetime->minute = 00;

                                    $room_reservation_end_datetime = Carbon::parse(date('Y-m-d', strtotime($request->date_of_visit[1])))->setTimezone('Asia/Manila');
                                    $room_reservation_end_datetime->hour = 11;
                                    $room_reservation_end_datetime->minute = 00;

                                    $newRoomReservation = RoomReservation::create([
                                        // 'room_id' => ..., // Set room ID for auto check-in; Either random or by first available number
                                        'room_id' => $roomAvailability['available_room_ids'][$i],
                                        'room_type_id' => $roomAvailability['room_type_id'],
                                        'booking_reference_number' => $newBooking->reference_number,
                                        'category' => 'booking',
                                        'status' => 'pending',
                                        'start_datetime' => $room_reservation_start_datetime,
                                        'end_datetime' => $room_reservation_end_datetime,
                                    ]);

                                    $rooms_to_save_as_inclusions = [];
                                    $last_room_rate = null;
                                    // $room_type_id = 1;
                                    $batch = 0;

                                    $room_allocation_used = [];

                                    foreach ($period as $date_period) {
                                        if ($date_period->format('Y-m-d') != $departure_date->format('Y-m-d')) {

                                            $room_allocation = RoomAllocation::where('entity', $selectedRoomTypes[0]['entity'])
                                                            ->whereDate('date', $date_period->format('Y-m-d'))
                                                            ->where('room_type_id', $room_type->id)
                                                            ->where('status', 'approved')
                                                            ->first();

                                            if (!isset($room_allocation)) {
                                                $connection->rollBack();
                                                $booking_error_count++;
                                                return response()->json(['error' => 'NO_ROOM_ALLOCATION', 'message' => 'No room allocation for ('. $room_type->property->name .') '.$room_type->name], 400);
                                            }
            
                                            if ( (($room_allocation['allocation'] - $room_allocation['used']) - 1) < 0 ) {
                                                $connection->rollBack();
                                                $booking_error_count++;
                                                return response()->json(['room_allocation'=> $room_allocation,'error' => 'ROOM_FULLY_BOOKED_2', 'message' => 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name. " allocation:".$room_allocation['allocation']. " ".$room_allocation['used']], 400);
                                                // return 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name;
                                            }
                                            
                                            $room_allocation_used[] = $room_allocation['id']; 
                                            // Update used column for room allocation per id
                                            RoomAllocation::where('id', $room_allocation['id'])
                                                    ->increment('used');

                                            $room_rate = RoomRate::where('room_type_id', $room_type->id)
                                                    ->whereDate('start_datetime', '<=', $date_period->format('Y-m-d'))
                                                    ->whereDate('end_datetime', '>=', $date_period->format('Y-m-d'))
                                                    ->whereRaw('json_contains(days_interval, \'["'. strtolower(Carbon::parse($date_period)->isoFormat('ddd')) .'"]\')')
                                                    // ->whereRaw('json_contains(allowed_roles, \'["'. $request->user()->roles[0]['name'] .'"]\')')
                                                    // ->whereRaw('json_contains(allowed_roles, \'["customer"]\')')
                                                    ->whereRaw('json_contains(allowed_roles, \'["Customer"]\')')
                                                    ->orderBy('created_at', 'desc')
                                                    ->where('status', 'approved')
                                                    ->first();

                                            if ($room_rate) {
                                                $isDayAllowed = in_array(strtolower(Carbon::parse($date_period)->isoFormat('ddd')), $room_rate->days_interval);
                                                $isDayExcluded = in_array($date_period->format('Y-m-d'), $room_rate->exclude_days);
                                            }

                                            $selling_rate = $room_type->rack_rate;

                                            if ($room_rate && $isDayAllowed == true && $isDayExcluded == false) {
                                                $selling_rate = $room_rate->room_rate;
                                            }
                                            
                                            if ($last_room_rate == null) {
                                                $last_room_rate = $selling_rate;
                                            }

                                            // Check if how many nights has the same room rate
                                            if ($selling_rate == $last_room_rate) {
                                                // $rooms_to_save_as_inclusions[$batch][][$last_room_rate][] = $date_period->format('Y-m-d');
                                                $rooms_to_save_as_inclusions[$batch]['rate'] = $last_room_rate;
                                                $rooms_to_save_as_inclusions[$batch]['dates'][] = $date_period->format('Y-m-d');
                                            } else {
                                                $batch = $batch + 1;
                                                $rooms_to_save_as_inclusions[$batch]['rate'] = $selling_rate;
                                                $rooms_to_save_as_inclusions[$batch]['dates'][] = $date_period->format('Y-m-d');
                                            }

                                            $last_room_rate = $selling_rate;

                                        }
                                    }

                                    // Update room reservation with the allocations used
                                    RoomReservation::where('id', $newRoomReservation->id)
                                        ->update([
                                            'allocation_used' => json_encode($room_allocation_used)
                                        ]);

                                    // return $rooms_to_save_as_inclusions;

                                    foreach ($rooms_to_save_as_inclusions as $key => $room_rate_data) {

                                        // return ($room_rate_data['dates']);
                                        // foreach ($savedIds as $pkg) {
                                            $per_booking_room_types_to_save[] = new Inclusion([
                                                'invoice_id' => isset($newBooking->invoices[0]) ? $newBooking->invoices[0]->id : 0,
                                                // 'parent_id' => $pkg['id'],
                                                'parent_id' => $per_guest_package_save->id,
                                                // 'guest_id' => null,
                                                'guest_id' => $guest['id'],
                                                'guest_reference_number' => $guest['reference_number'],
                                                'item' => "(".$room_type->property->name.") ".$room_type->name,
                                                'code' => $room_type->property->code."-".$room_type->code."_".count($room_rate_data['dates'])."NIGHTS_".$room_rate_data['dates'][0]."-to-".end($room_rate_data['dates']),
                                                // 'code' => $room_type->property->code."-".$room_type->code."_".$room_reservation_start_datetime->format('Y-m-d_Hi')."-".$room_reservation_end_datetime->format('Y-m-d_Hi'),
                                                'type' => 'room_reservation',
                                                'description' => null,
                                                'serving_time' => null,
                                                'used_at' => null,
                                                'quantity' => 1,
                                                'original_price' => $room_rate_data['rate'], // update this
                                                'price' => 0, // update this
                                                'walkin_price' => 0,
                                                'selling_price' => 0,
                                                'discount' => null,
                                                // 'created_by' => $request->user()->id,
                                                'created_by' => null,
                                            ]);
                                        // }

                                        // Update invoice total cost
                                        // $invoice_total_cost = $invoice_total_cost + ($room_rate_data['rate'] * count($room_rate_data['dates'])); //

                                    }

                                }

                            // } // quantity

                        // }
                        } else {
                            $connection->rollBack();
                            return response()->json(['error' => 'NO_ROOM_AVAILABLE', 'message' => 'No room available for this package.'], 400);
                        }

                        $newBooking->inclusions()->saveMany($per_booking_room_types_to_save);


                    }
            }
            // End of per guest packages
            
            /**
             * Add passes for each guest
             * Add guest passes
             */

            /**
             * Camaya Transport Booking per guest
             */

             // Check if guest can get the seat allocation segment
             // Arrival; 1st trip
             if ($request->firstTrip) {
                $seat_segment_1 = SeatSegment::where('id', $request->firstTrip['seat_segment_id'])
                                        ->with(['schedule' => function ($q) {
                                            // $q->with('transportation');
                                        }])
                                        ->first();

                $available_seat_1 = $seat_segment_1->allocated - $seat_segment_1->used;

                if ($available_seat_1 <= 0) {
                    $connection->rollBack();
                    $booking_error_count++;
                    return response()->json(['error' => 'FIRST_TRIP_FULLY_BOOKED', 'message' => 'First trip is fully booked.'], 400);
                }

                // Get all seat number on a trip
                $trip_seat_numbers_1 = Trip::where('trip_number', $seat_segment_1['trip_number'])
                                            ->whereIn('status', ['boarded', 'checked_in', 'pending'])
                                            ->pluck('seat_number')->toArray();
                // Arrival seat number
                $seat_1 = Seat::whereNotIn('number', array_filter($trip_seat_numbers_1,'strlen'))
                                        ->where('status', 'active')
                                        ->whereNotIn('auto_check_in_status', ['restricted', 'vip'])
                                        ->orderBy('order', 'asc')
                                        ->first();

                if (!$seat_1) {
                    $connection->rollBack();
                    $booking_error_count++;
                    return response()->json(['error' => 'NO_MORE_SEAT_AVAILABLE', 'message' => 'No more seat available'], 400);
                }

                /**
                 * Increment used allocation on segment
                 */
                if ($guest['type'] != 'infant') {
                    SeatSegment::where('id', $seat_segment_1['id'])
                                ->increment('used');
                }

                // Create passenger record
                $newPassenger_1 = \App\Models\Transportation\Passenger::create([
                    'trip_number' => $seat_segment_1['trip_number'],
                    'booking_reference_number' => $newBooking['reference_number'],
                    'guest_reference_number' => $guest['reference_number'],
                    'first_name' => $guest['first_name'],
                    'last_name' => $guest['last_name'],
                    'age' => $guest['age'],
                    'nationality' => $guest['nationality'] ?? null,
                    'type' => $guest['type'],
                ]);

                // Create 1st trip record
                $newTrip_1 = Trip::create([
                    'trip_number' => $seat_segment_1['trip_number'],
                    'ticket_reference_number' => 1,
                    'guest_reference_number' => $guest['reference_number'],
                    'booking_reference_number' => $newBooking['reference_number'],
                    'passenger_id' => $newPassenger_1->id,
                    'seat_number' => ($guest['type'] != 'infant') ? $seat_1->number : null,
                    'status' => 'pending',
                    'seat_segment_id' => $seat_segment_1['id'],
                    'printed' => 0,
                    'last_printed_at' => null,
                    'checked_in_at' => Carbon::now(),
                    'boarded_at' => null,
                    'cancelled_at' => null,
                    'no_show_at' => null,
                ]);

                // Create inclusion for camaya transportation ticket
                // Increment invoice total cost
                $ticket_original_price = ($guest['age'] <= $infant_max_age) ? 0 : $seat_segment_1->rate; // Change this to actual ticket price
                $ticket_selling_price = collect($request->selectedPackages)->firstWhere('camaya_transportation_available', true) ? 0 : $ticket_original_price;
                $ticket_selling_price = $guest['age'] <= $infant_max_age ? 0 : $ticket_selling_price;
                $invoice_total_cost = $invoice_total_cost + $ticket_selling_price;

                $per_guest_inclusions_ticket_1 = $newBooking->inclusions()->create([
                    'invoice_id' => $newBooking->invoices[0]->id,
                    'guest_id' => $guest['id'],
                    'guest_reference_number' => $guest['reference_number'],
                    'item' => 'Trip Ticket : '.$seat_segment_1['trip_number'],
                    'code' => $seat_segment_1['trip_number'].'_'.$guest['reference_number'].'_TICKET',
                    'description' => 'Camaya Transportation ticket',
                    'type' => 'ticket',
                    'serving_time' => null,
                    'used_at' => null,
                    'quantity' => 1,
                    'original_price' => $ticket_original_price ?? 0,
                    'price' => $ticket_selling_price,
                    'walkin_price' => 0,
                    'selling_price' => 0,
                    'discount' => null,
                    'created_by' => null,
                ]);

                // Create Passes
                $schedule_datetime_1 = $seat_segment_1->schedule->trip_date." ".$seat_segment_1->schedule->start_time;
                $boarding_time_1 = Carbon::parse($schedule_datetime_1)->subHours(1)->format('H:i:s'); // 1 hours before departure
                $boarding_time_expires_1 = Carbon::parse($schedule_datetime_1)->addMinutes(30)->format('H:i:s'); // 30 minutes after departure
                Pass::createBoardingPass(
                    $booking_reference_number,
                    $guest['reference_number'],
                    $newTrip_1->id,
                    $seat_segment_1['trip_number'],
                    ($guest['type'] != 'infant') ? $seat_1->number : 'Infant',
                    $seat_segment_1->schedule->trip_date,
                    $boarding_time_1,
                    $boarding_time_expires_1,
                    $per_guest_inclusions_ticket_1->id
                );

                //Create Product Pass for FTT Entry
                $ftt_entry_stub = Stub::where('type', "FTT Pass Entry")->first();
                Pass::createProductPasses($ftt_entry_stub['id'], $booking_reference_number, $guest['reference_number'], $arrival_date, $departure_date, null);
                
            }

            // 2nd trip
            if ($request->secondTrip) {
                $seat_segment_2 = SeatSegment::where('id', $request->secondTrip['seat_segment_id'])
                                        ->with(['schedule' => function ($q) {
                                            // $q->with('transportation');
                                        }])
                                        ->first();

                $available_seat_2 = $seat_segment_2->allocated - $seat_segment_2->used;

                if ($available_seat_2 <= 0) {
                    $connection->rollBack();
                    $booking_error_count++;
                    return response()->json(['error' => 'FIRST_TRIP_FULLY_BOOKED', 'message' => 'First trip is fully booked.'], 400);
                }

                // Get all seat number on a trip
                $trip_seat_numbers_2 = Trip::where('trip_number', $seat_segment_2['trip_number'])
                                            ->whereIn('status', ['boarded', 'checked_in', 'pending'])
                                            ->pluck('seat_number')->toArray();
                // Arrival seat number
                $seat_2 = Seat::whereNotIn('number', array_filter($trip_seat_numbers_2,'strlen'))
                                        ->where('status', 'active')
                                        ->whereNotIn('auto_check_in_status', ['restricted', 'vip'])
                                        ->orderBy('order', 'asc')
                                        ->first();

                if (!$seat_2) {
                    $connection->rollBack();
                    $booking_error_count++;
                    return response()->json(['error' => 'NO_MORE_SEAT_AVAILABLE', 'message' => 'No more seat available'], 400);
                }

                /**
                 * Increment used allocation on segment
                 */
                if ($guest['type'] != 'infant') {
                    SeatSegment::where('id', $seat_segment_2['id'])
                                ->increment('used');
                }

                // Create passenger record
                $newPassenger_2 = \App\Models\Transportation\Passenger::create([
                    'trip_number' => $seat_segment_2['trip_number'],
                    'booking_reference_number' => $newBooking['reference_number'],
                    'guest_reference_number' => $guest['reference_number'],
                    'first_name' => $guest['first_name'],
                    'last_name' => $guest['last_name'],
                    'age' => $guest['age'],
                    'nationality' => $guest['nationality'] ?? null,
                    'type' => $guest['type'],
                ]);

                // Create 2nd trip record
                $newTrip_2 = Trip::create([
                    'trip_number' => $seat_segment_2['trip_number'],
                    'ticket_reference_number' => 1,
                    'guest_reference_number' => $guest['reference_number'],
                    'booking_reference_number' => $newBooking['reference_number'],
                    'passenger_id' => $newPassenger_2->id,
                    'seat_number' => ($guest['type'] != 'infant') ? $seat_2->number : null,
                    'status' => 'pending',
                    'seat_segment_id' => $seat_segment_2['id'],
                    'printed' => 0,
                    'last_printed_at' => null,
                    'checked_in_at' => Carbon::now(),
                    'boarded_at' => null,
                    'cancelled_at' => null,
                    'no_show_at' => null,
                ]);

                // Create inclusion for camaya transportation ticket
                // Increment invoice total cost
                $ticket_original_price = ($guest['age'] <= $infant_max_age) ? 0 : $seat_segment_2->rate; // Change this to actual ticket price
                $ticket_selling_price = collect($request->selectedPackages)->firstWhere('camaya_transportation_available', true) ? 0 : $ticket_original_price;
                $ticket_selling_price = $guest['age'] <= $infant_max_age ? 0 : $ticket_selling_price;
                $invoice_total_cost = $invoice_total_cost + $ticket_selling_price;

                $per_guest_inclusions_ticket_2 = $newBooking->inclusions()->create([
                    'invoice_id' => $newBooking->invoices[0]->id,
                    'guest_id' => $guest['id'],
                    'guest_reference_number' => $guest['reference_number'],
                    'item' => 'Trip Ticket : '.$seat_segment_2['trip_number'],
                    'code' => $seat_segment_2['trip_number'].'_'.$guest['reference_number'].'_TICKET',
                    'description' => 'Camaya Transportation ticket',
                    'type' => 'ticket',
                    'serving_time' => null,
                    'used_at' => null,
                    'quantity' => 1,
                    'original_price' => $ticket_original_price ?? 0,
                    'price' => $ticket_selling_price,
                    'walkin_price' => 0,
                    'selling_price' => 0,
                    'discount' => null,
                    'created_by' => null,
                ]);
                

                // Create Passes
                $schedule_datetime_2 = $seat_segment_2->schedule->trip_date." ".$seat_segment_2->schedule->start_time;
                $boarding_time_2 = Carbon::parse($schedule_datetime_2)->setTimezone('Asia/Manila')->subHours(1)->format('H:i:s'); // 1 hours before departure
                $boarding_time_expires_2 = Carbon::parse($schedule_datetime_2)->setTimezone('Asia/Manila')->addMinutes(30)->format('H:i:s'); // 30 minutes after departure
                Pass::createBoardingPass(
                    $booking_reference_number,
                    $guest['reference_number'],
                    $newTrip_2->id,
                    $seat_segment_2['trip_number'],
                    ($guest['type'] != 'infant') ? $seat_2->number : 'Infant',
                    $seat_segment_2->schedule->trip_date,
                    $boarding_time_2,
                    $boarding_time_expires_2,
                    $per_guest_inclusions_ticket_2->id
                );

                //Create Product Pass for FTT Exit
                $ftt_entry_stub = Stub::where('type', "FTT Pass Exit")->first();
                Pass::createProductPasses($ftt_entry_stub['id'], $booking_reference_number, $guest['reference_number'], $arrival_date, $departure_date, null);
            }

        }

        $newBooking->inclusions()->saveMany($per_guest_inclusions_to_save);


        /**
         * Add per booking inclusions
         */

        $per_booking_inclusions_to_save = [];

        foreach ($perBookingProducts->all() as $item) {

                $selectedProduct = collect($request->selectedProducts)->firstWhere('code', $item['code']);

                // Increment invoice total cost
                
                if (!$selectedProduct['quantity']) {
                    $invoice_total_cost = $invoice_total_cost + $item['price'];
                } else {
                    $invoice_total_cost = $invoice_total_cost + $item['price'] * $selectedProduct['quantity'];
                }

                $per_booking_inclusions_to_save[] = new Inclusion([
                    'invoice_id' => $newBooking->invoices[0]->id,
                    'guest_id' => null,
                    'item' => $item['name'],
                    'code' => $item['code'],
                    'type' => 'product',
                    'description' => $item['description'],
                    // 'serving_time' => $item['serving_time'],
                    'serving_time' => isset($item['serving_time']) ? Carbon::parse($item['serving_time'][0])->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
                    'used_at' => null,
                    'quantity' => $selectedProduct['quantity'],
                    'original_price' => $item['price'],
                    'price' => $item['price'],
                    'walkin_price' => $item['walkin_price'],
                    'selling_price' => $item['price'],
                    'discount' => null,
                    'created_by' => null,
                ]);

        }

        $newBooking->inclusions()->saveMany($per_booking_inclusions_to_save);

        /**
         *  Add per booking packages
         */
        foreach ($perBookingPackages->all() as $item) {
            // Increment invoice total cost
            $selectedPackage = collect($request->selectedPackages)->firstWhere('code', $item['code']);

            $savedIds = [];
            if ($nights >= 1) {
                for ($i = 0; $i < $nights; $i++) {

                    // Identify the selling price
                    // Identify if the selling price is weekday or weekend

                    $selling_price_type = null;
                    
                    if ($item['weekday_rate'] > 0 && in_array(Carbon::parse($array_period_dates[$i])->format('l'), $ovn_weekday)) {
                        $per_booking_package_selling_price = $item['weekday_rate'];
                        $selling_price_type = 'Weekday Rate';
                    } else if ($item['weekend_rate'] > 0 && in_array(Carbon::parse($array_period_dates[$i])->format('l'), $ovn_weekend)) {
                        $per_booking_package_selling_price = $item['weekend_rate'];
                        $selling_price_type = 'Weekend Rate';
                    } else { 
                        $per_booking_package_selling_price = $item['selling_price'];
                    }
                    
                    // Increment invoice total cost
                    if (!$selectedPackage['quantity']) {
                        $invoice_total_cost = $invoice_total_cost + $per_booking_package_selling_price;
                    } else {
                        $invoice_total_cost = $invoice_total_cost + $per_booking_package_selling_price * $selectedPackage['quantity'];
                    }

                    $per_booking_package_save = $newBooking->inclusions()->create([
                        'invoice_id' => $newBooking->invoices[0]->id,
                        'guest_id' => null,
                        'item' => $item['name'],
                        'code' => $item['code'],
                        'type' => 'package',
                        'description' => null,
                        'serving_time' => null,
                        'used_at' => null,
                        'quantity' => $selectedPackage['quantity'] ?? 1,
                        'original_price' => $item['regular_price'],
                        'price' => $per_booking_package_selling_price,
                        'walkin_price' => $item['walkin_price'],
                        'selling_price' => $per_booking_package_selling_price,
                        'selling_price_type' => $selling_price_type,
                        'discount' => null,
                        'created_by' => null,
                    ]);

                    $savedIds[] = $per_booking_package_save;

                
                    $per_booking_package_to_save = [];

                    foreach ($item['packageInclusions'] as $package_inclusions) {
                        if ($package_inclusions['type'] == 'product') {

                            $package_inclusion_product = Product::where('code', $package_inclusions['product']['code'])->with('productPass')->first();

                            // return $package_inclusions;

                            if ($package_inclusions['product']['type'] == 'per_booking') {
                                $per_booking_package_to_save[] = new Inclusion([
                                    'invoice_id' => $newBooking->invoices[0]->id,
                                    'guest_id' => null,
                                    'parent_id' => $per_booking_package_save->id,
                                    'item' => $package_inclusions['product']['name'],
                                    'code' => $package_inclusions['product']['code'],
                                    'type' => 'package_inclusion',
                                    'description' => null,
                                    'serving_time' => null,
                                    'used_at' => null,
                                    'quantity' => ($package_inclusions['quantity'] * $selectedPackage['quantity']) ?? 1,
                                    'original_price' => $package_inclusions['product']['price'],
                                    'price' => 0,
                                    'walkin_price' => $package_inclusions['product']['walkin_price'],
                                    'selling_price' => $package_inclusions['product']['price'],
                                    'discount' => null,
                                    'created_by' => null,
                                ]);
                            } else if ($package_inclusions['product']['type'] == 'per_guest') {

                                foreach ($newBooking->guests as $guest) {
                        
                                        $price = $package_inclusions['product']['price'];
                        
                                        if ($guest['type'] == 'kid') $price = isset($package_inclusion_product['kid_price']) ? $package_inclusion_product['kid_price'] : $package_inclusion_product['price'];
                                        if ($guest['age'] <= $infant_max_age) $price = isset($package_inclusion_product['infant_price']) ? $package_inclusion_product['infant_price'] : $package_inclusion_product['price'];

                                        $per_booking_package_inclusion = $newBooking->inclusions()->create([
                                            'invoice_id' => $newBooking->invoices[0]->id,
                                            'guest_id' => $guest['id'],
                                            'parent_id' => $per_booking_package_save->id,
                                            'item' => $package_inclusions['product']['name'],
                                            'code' => $package_inclusions['product']['code'],
                                            'type' => 'package_inclusion',
                                            'description' => null,
                                            'serving_time' => null,
                                            'used_at' => null,
                                            'quantity' => 1,
                                            'original_price' => $price,
                                            'price' => 0,
                                            'walkin_price' => $package_inclusions['product']['walkin_price'],
                                            'selling_price' => $package_inclusions['product']['price'],
                                            'discount' => null,
                                            'created_by' => null,
                                        ]);

                                        if ($package_inclusion_product['quantity_per_day'] > 0) {
                                            \App\Models\Booking\Addon::create([
                                                'booking_reference_number' => $newBooking['reference_number'],
                                                'guest_reference_number' => $guest['reference_number'],
                                                'code' => $package_inclusion_product['code'],
                                                'date' => $arrival_date,
                                                'status' => 'valid',
                                                // 'created_by' => $request->user()->id,
                                            ]);
                                        }

                                        /**
                                         * Create Passes when product has pass stub
                                         */
                                        if (isset($package_inclusion_product['productPass'])) {
                                            foreach ($package_inclusion_product['productPass'] as $product_pass) {

                                                if ($main_gate_pass->id != $product_pass['stub_id']) {
                                                    Pass::createProductPasses($product_pass['stub_id'], $booking_reference_number, $guest['reference_number'], $arrival_date, $departure_date, $per_booking_package_inclusion->id);
                                                }
                                                
                                            }
                                        }

                                    }
                                }
                        }
                    }

                    $newBooking->inclusions()->saveMany($per_booking_package_to_save);
                } // working here
            } else {

                $per_booking_package_dtt_price = $item['selling_price'];

                $selling_price_type = null;

                if ($item['weekday_rate'] > 0 && in_array(Carbon::parse($arrival_date)->format('l'), $dtt_weekday)) {
                    $per_booking_package_dtt_price = $item['weekday_rate'];
                    $selling_price_type = 'Weekday Rate';
                } else if ($item['weekend_rate'] > 0 && in_array(Carbon::parse($arrival_date)->format('l'), $dtt_weekend)) {
                    $per_booking_package_dtt_price = $item['weekend_rate'];
                    $selling_price_type = 'Weekend Rate';
                } else { 
                    $per_booking_package_dtt_price = $item['selling_price'];
                }

                // Increment invoice total cost
                if (!$selectedPackage['quantity']) {
                    $invoice_total_cost = $invoice_total_cost + $per_booking_package_dtt_price;
                } else {
                    $invoice_total_cost = $invoice_total_cost + $per_booking_package_dtt_price * $selectedPackage['quantity'];
                }

                $per_booking_package_save = $newBooking->inclusions()->create([
                    'invoice_id' => $newBooking->invoices[0]->id,
                    'guest_id' => null,
                    'item' => $item['name'],
                    'code' => $item['code'],
                    'type' => 'package',
                    'description' => null,
                    'serving_time' => null,
                    'used_at' => null,
                    'quantity' => $selectedPackage['quantity'] ?? 1,
                    'original_price' => $item['regular_price'],
                    'price' => $per_booking_package_dtt_price,
                    'walkin_price' => $item['walkin_price'],
                    'selling_price' => $per_booking_package_dtt_price,
                    'selling_price_type' => $selling_price_type,
                    'discount' => null,
                    'created_by' => null,
                ]);

                $savedIds[] = $per_booking_package_save;

            
                $per_booking_package_to_save = [];

                foreach ($item['packageInclusions'] as $package_inclusions) {
                    if ($package_inclusions['type'] == 'product') {

                        $package_inclusion_product = Product::where('code', $package_inclusions['product']['code'])->with('productPass')->first();

                        // return $package_inclusions;

                        if ($package_inclusions['product']['type'] == 'per_booking') {
                            $per_booking_package_to_save[] = new Inclusion([
                                'invoice_id' => $newBooking->invoices[0]->id,
                                'guest_id' => null,
                                // 'guest_id' => $guest['id'],
                                // 'guest_reference_number' => $guest['reference_number'],
                                'parent_id' => $per_booking_package_save->id,
                                'item' => $package_inclusions['product']['name'],
                                'code' => $package_inclusions['product']['code'],
                                'type' => 'package_inclusion',
                                'description' => null,
                                'serving_time' => null,
                                'used_at' => null,
                                'quantity' => ($package_inclusions['quantity'] * $selectedPackage['quantity']) ?? 1,
                                'original_price' => $package_inclusions['product']['price'],
                                'price' => 0,
                                'walkin_price' => $package_inclusions['product']['walkin_price'],
                                'selling_price' => $package_inclusions['product']['price'],
                                'discount' => null,
                                'created_by' => null,
                            ]);
                        } else if ($package_inclusions['product']['type'] == 'per_guest') {

                            foreach ($newBooking->guests as $guest) {
                    
                                    $price = $package_inclusions['product']['price'];
                    
                                    if ($guest['type'] == 'kid') $price = isset($package_inclusion_product['kid_price']) ? $package_inclusion_product['kid_price'] : $package_inclusion_product['price'];
                                    if ($guest['age'] <= $infant_max_age) $price = isset($package_inclusion_product['infant_price']) ? $package_inclusion_product['infant_price'] : $package_inclusion_product['price'];

                                    $per_booking_package_inclusion = $newBooking->inclusions()->create([
                                        'invoice_id' => $newBooking->invoices[0]->id,
                                        'guest_id' => $guest['id'],
                                        'parent_id' => $per_booking_package_save->id,
                                        'item' => $package_inclusions['product']['name'],
                                        'code' => $package_inclusions['product']['code'],
                                        'type' => 'package_inclusion',
                                        'description' => null,
                                        'serving_time' => null,
                                        'used_at' => null,
                                        'quantity' => 1,
                                        'original_price' => $price,
                                        'price' => 0,
                                        'walkin_price' => $package_inclusions['product']['walkin_price'],
                                        'selling_price' => $package_inclusions['product']['price'],
                                        'discount' => null,
                                        'created_by' => null,
                                    ]);

                                    /**
                                     * Create Passes when product has pass stub
                                     */
                                    if (isset($package_inclusion_product['productPass'])) {
                                        foreach ($package_inclusion_product['productPass'] as $product_pass) {

                                            if ($main_gate_pass->id != $product_pass['stub_id']) {
                                                Pass::createProductPasses($product_pass['stub_id'], $booking_reference_number, $guest['reference_number'], $arrival_date, $departure_date, $per_booking_package_inclusion->id);
                                            }
                                            
                                        }
                                    }

                                }
                            }
                    }
                }

                $newBooking->inclusions()->saveMany($per_booking_package_to_save);

            }
                /**
                 *  Add package room reservations
                 */

                $roomTypesFromPackage = collect($item['packageRoomTypeInclusions']);

                $selectedRoomTypes = [];

                foreach ($item['packageRoomTypeInclusions'] as $room_type) {
                    $selectedRoomTypes[] = [
                        'room_type_id' => $room_type['related_id'],
                        'quantity' => $selectedPackage['quantity'],
                        'entity' => $room_type['entity'],
                    ];
                }

                $room_capacity = 0;
                $extra_pax = 0;

                $per_booking_room_types_to_save = [];
                $extra_pax_inclusions_to_save = [];

                // Checks if room type is selected
                if ($selectedRoomTypes) {
                    // Check if the rooms are still available
                    // $connection->rollBack();
                    $getAvailableRooms = RoomReservation::getAvailableRooms($selectedRoomTypes, $arrival_date, $departure_date);
                    $roomAvailability = collect($getAvailableRooms)->first(function ($item, $key) {
                        return ($item['total'] - $item['booked']) > 0;
                        // return (count($item['available_room_ids'])) > 0;
                    });
                    
                    if ($roomAvailability) {
                    
                    // Reserve the rooms and check if they can reserve the room
                    // Loop here

                    // foreach ($selectedRoomTypes as $room_types_to_reserve) {

                        $room_type = RoomType::where('id', $roomAvailability['room_type_id'])->with('property')->first();
                        $room_capacity = $selectedPackage['quantity'] * $room_type['capacity'];

                        if ($selectedPackage['quantity']) {

                            // Check if the room can accommodate the quantity
                            // $availableRooms = $roomAvailability['total'] - $roomAvailability['booked']; // DOES NOT CONSIDER BLOCKED OUT ROOMS
                            // $availableRooms = count($roomAvailability['available_room_ids']) - $roomAvailability['booked']; // WILL INCLUDE BLOCKOUT ROOMS
                            $availableRooms = count($roomAvailability['available_room_ids']);
                            
                            if ( ($availableRooms - $selectedPackage['quantity']) < 0 ) {
                                $connection->rollBack();
                                $booking_error_count++;
                                return response()->json(['error' => 'ROOM_FULLY_BOOKED', 'message' => 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name], 400);
                                // return 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name;
                            }


                            // Loop days period room
                            for ($i = 0; $i < $selectedPackage['quantity']; $i++) {

                                $room_reservation_start_datetime = Carbon::parse(date('Y-m-d', strtotime($request->date_of_visit[0])))->setTimezone('Asia/Manila');
                                $room_reservation_start_datetime->hour = 12;
                                $room_reservation_start_datetime->minute = 00;

                                $room_reservation_end_datetime = Carbon::parse(date('Y-m-d', strtotime($request->date_of_visit[1])))->setTimezone('Asia/Manila');
                                $room_reservation_end_datetime->hour = 11;
                                $room_reservation_end_datetime->minute = 00;

                                $newRoomReservation = RoomReservation::create([
                                    // 'room_id' => ..., // Set room ID for auto check-in; Either random or by first available number
                                    'room_id' => $roomAvailability['available_room_ids'][$i],
                                    'room_type_id' => $roomAvailability['room_type_id'],
                                    'booking_reference_number' => $newBooking->reference_number,
                                    'category' => 'booking',
                                    'status' => 'pending',
                                    'start_datetime' => $room_reservation_start_datetime,
                                    'end_datetime' => $room_reservation_end_datetime,
                                ]);

                                $rooms_to_save_as_inclusions = [];
                                $last_room_rate = null;
                                // $room_type_id = 1;
                                $batch = 0;

                                $room_allocation_used = [];

                                foreach ($period as $date_period) {
                                    if ($date_period->format('Y-m-d') != $departure_date->format('Y-m-d')) {

                                        $room_allocation = RoomAllocation::where('entity', $selectedRoomTypes[0]['entity'])
                                                        ->whereDate('date', $date_period->format('Y-m-d'))
                                                        ->where('room_type_id', $room_type->id)
                                                        ->where('status', 'approved')
                                                        ->first();

                                        if (!isset($room_allocation)) {
                                            $connection->rollBack();
                                            $booking_error_count++;
                                            return response()->json(['error' => 'NO_ROOM_ALLOCATION', 'message' => 'No room allocation for ('. $room_type->property->name .') '.$room_type->name], 400);
                                        }
        
                                        if ( (($room_allocation['allocation'] - $room_allocation['used']) - 1) < 0 ) {
                                            $connection->rollBack();
                                            $booking_error_count++;
                                            return response()->json(['error' => 'ROOM_FULLY_BOOKED', 'message' => 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name], 400);
                                            // return 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name;
                                        }
                                        
                                        $room_allocation_used[] = $room_allocation['id']; 
                                        // Update used column for room allocation per id
                                        RoomAllocation::where('id', $room_allocation['id'])
                                                ->increment('used');

                                        $room_rate = RoomRate::where('room_type_id', $room_type->id)
                                                ->whereDate('start_datetime', '<=', $date_period->format('Y-m-d'))
                                                ->whereDate('end_datetime', '>=', $date_period->format('Y-m-d'))
                                                ->whereRaw('json_contains(days_interval, \'["'. strtolower(Carbon::parse($date_period)->isoFormat('ddd')) .'"]\')')
                                                // ->whereRaw('json_contains(allowed_roles, \'["'. $request->user()->roles[0]['name'] .'"]\')')
                                                ->whereRaw('json_contains(allowed_roles, \'["Customer"]\')')
                                                ->orderBy('created_at', 'desc')
                                                ->where('status', 'approved')
                                                ->first();

                                        if ($room_rate) {
                                            $isDayAllowed = in_array(strtolower(Carbon::parse($date_period)->isoFormat('ddd')), $room_rate->days_interval);
                                            $isDayExcluded = in_array($date_period->format('Y-m-d'), $room_rate->exclude_days);
                                        }

                                        $selling_rate = $room_type->rack_rate;

                                        if ($room_rate && $isDayAllowed == true && $isDayExcluded == false) {
                                            $selling_rate = $room_rate->room_rate;
                                        }
                                        
                                        if ($last_room_rate == null) {
                                            $last_room_rate = $selling_rate;
                                        }

                                        // Check if how many nights has the same room rate
                                        if ($selling_rate == $last_room_rate) {
                                            // $rooms_to_save_as_inclusions[$batch][][$last_room_rate][] = $date_period->format('Y-m-d');
                                            $rooms_to_save_as_inclusions[$batch]['rate'] = $last_room_rate;
                                            $rooms_to_save_as_inclusions[$batch]['dates'][] = $date_period->format('Y-m-d');
                                        } else {
                                            $batch = $batch + 1;
                                            $rooms_to_save_as_inclusions[$batch]['rate'] = $selling_rate;
                                            $rooms_to_save_as_inclusions[$batch]['dates'][] = $date_period->format('Y-m-d');
                                        }

                                        $last_room_rate = $selling_rate;

                                    }
                                }

                                // Update room reservation with the allocations used
                                RoomReservation::where('id', $newRoomReservation->id)
                                    ->update([
                                        'allocation_used' => json_encode($room_allocation_used)
                                    ]);

                                // return $rooms_to_save_as_inclusions;

                                foreach ($rooms_to_save_as_inclusions as $key => $room_rate_data) {

                                    // return ($room_rate_data['dates']);
                                    foreach ($savedIds as $pkg) {
                                        $per_booking_room_types_to_save[] = new Inclusion([
                                            'invoice_id' => isset($newBooking->invoices[0]) ? $newBooking->invoices[0]->id : 0,
                                            'parent_id' => $pkg['id'],
                                            'guest_id' => null,
                                            'item' => "(".$room_type->property->name.") ".$room_type->name,
                                            'code' => $room_type->property->code."-".$room_type->code."_".count($room_rate_data['dates'])."NIGHTS_".$room_rate_data['dates'][0]."-to-".end($room_rate_data['dates']),
                                            // 'code' => $room_type->property->code."-".$room_type->code."_".$room_reservation_start_datetime->format('Y-m-d_Hi')."-".$room_reservation_end_datetime->format('Y-m-d_Hi'),
                                            'type' => 'room_reservation',
                                            'description' => null,
                                            'serving_time' => null,
                                            'used_at' => null,
                                            'quantity' => 1,
                                            'original_price' => $room_type->rack_rate,
                                            'price' => 0, // update this
                                            'walkin_price' => 0,
                                            'selling_price' => 0,
                                            'discount' => null,
                                            'created_by' => null,
                                        ]);
                                    }

                                    // Update invoice total cost
                                    // $invoice_total_cost = $invoice_total_cost + ($room_rate_data['rate'] * count($room_rate_data['dates'])); //

                                }

                            }

                        }

                    // }
                    } else {
                        $connection->rollBack();
                        return response()->json(['error' => 'ROOM_FULLY_BOOKED', 'message' => 'Fully booked'], 400);
                    }

                    $extra_pax = ($adult_count - $room_capacity) < 0 ? 0 : ($adult_count - $room_capacity);

                    if ($extra_pax > 0) {

                        $extraPaxProduct = Product::where('code', 'EXTRAPAX')->first();

                        if ($extraPaxProduct) {
                            $extra_pax_inclusions_to_save[] = new Inclusion([
                                'invoice_id' => $newBooking->invoices[0]->id,
                                'guest_id' => null,
                                'item' => $extraPaxProduct['name'],
                                'code' => $extraPaxProduct['code'],
                                'type' => 'product',
                                'description' => $extraPaxProduct['description'],
                                'serving_time' => $extraPaxProduct['serving_time'],
                                'used_at' => null,
                                'quantity' => $extra_pax * $nights,
                                'original_price' => $extraPaxProduct['price'],
                                'price' => $extraPaxProduct['price'],
                                'walkin_price' => 0,
                                'selling_price' => 0,
                                'discount' => null,
                                'created_by' => null,
                            ]);
                            
                            //disable extra pax
                            // $newBooking->inclusions()->saveMany($extra_pax_inclusions_to_save);
                            // $invoice_total_cost = $invoice_total_cost + ($extraPaxProduct['price'] * $extra_pax * $nights);
                        }
                    }

                    $newBooking->inclusions()->saveMany($per_booking_room_types_to_save);


                }
            // } // test for loop not working when it's here
        }

        /**
         *  Add room reservations
         */

        // Checks if room type is selected
        if ($request->selectedRoomTypes) {
            // Check if the rooms are still available
            // $connection->rollBack();
            $getAvailableRooms = RoomReservation::getAvailableRooms($request->selectedRoomTypes, $arrival_date, $departure_date);

            // return $getAvailableRooms;
            // Reserve the rooms and check if they can reserve the room
            // Loop here

            $per_booking_room_types_to_save = [];

            foreach ($request->selectedRoomTypes as $room_types_to_reserve) {

                $room_type = RoomType::where('id', $room_types_to_reserve['room_type_id'])->with('property')->first();

                if ($room_types_to_reserve['quantity']) {

                    // Check if the room can accommodate the quantity
                    $roomAvailability = collect($getAvailableRooms)->firstWhere('room_type_id', $room_types_to_reserve['room_type_id']);

                    // return [$roomAvailability];

                    // if (!$roomAvailability) {
                    if (!isset($roomAvailability) && count($roomAvailability['available_room_ids']) < $room_types_to_reserve['quantity']) {
                        $connection->rollBack();
                        $booking_error_count++;
                        return response()->json(['error' => 'ROOM_FULLY_BOOKED', 'message' => 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name], 400);
                    }
                    //*** */ $availableRooms = $roomAvailability['total'] - $roomAvailability['booked'];
                    // if ( ($availableRooms - $room_types_to_reserve['quantity']) < 0 ) {
                    //     $connection->rollBack();

                    //     return response()->json(['error' => 'ROOM_FULLY_BOOKED', 'message' => 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name], 400);
                    //     // return 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name;
                    // }


                    // Loop days period room
                    
                    for ($i = 0; $i < $room_types_to_reserve['quantity']; $i++) {

                        $room_reservation_start_datetime = Carbon::parse(date('Y-m-d', strtotime($request->date_of_visit[0])))->setTimezone('Asia/Manila');
                        $room_reservation_start_datetime->hour = 12;
                        $room_reservation_start_datetime->minute = 00;

                        $room_reservation_end_datetime = Carbon::parse(date('Y-m-d', strtotime($request->date_of_visit[1])))->setTimezone('Asia/Manila');
                        $room_reservation_end_datetime->hour = 11;
                        $room_reservation_end_datetime->minute = 00;

                        $newRoomReservation = RoomReservation::create([
                            // 'room_id' => ..., // Set room ID for auto check-in; Either random or by first available number
                            'room_id' => $roomAvailability['available_room_ids'][$i],
                            'room_type_id' => $room_types_to_reserve['room_type_id'],
                            'booking_reference_number' => $newBooking->reference_number,
                            'category' => 'booking',
                            'status' => 'pending',
                            'start_datetime' => $room_reservation_start_datetime,
                            'end_datetime' => $room_reservation_end_datetime,
                        ]);

                        $room_allocation_used = [];

                        $rooms_to_save_as_inclusions = [];
                        $last_room_rate = null;
                        // $room_type_id = 1;
                        $batch = 0;

                        foreach ($period as $date_period) {
                            if ($date_period->format('Y-m-d') != $departure_date->format('Y-m-d')) {

                                // Check room allocations if available
                                $room_allocation = RoomAllocation::where('entity', $room_types_to_reserve['entity'])
                                                    ->whereDate('date', $date_period->format('Y-m-d'))
                                                    ->where('room_type_id', $room_type->id)
                                                    ->where('status', 'approved')
                                                    ->first();
                                // return [
                                //     $room_allocation,
                                //     $room_types_to_reserve['quantity']
                                // ];

                                if (!isset($room_allocation)) {
                                    $connection->rollBack();
                                    $booking_error_count++;
                                    return response()->json(['error' => 'NO_ROOM_ALLOCATION', 'message' => 'No room allocation for ('. $room_type->property->name .') '.$room_type->name], 400);
                                }

                                if ( (($room_allocation['allocation'] - $room_allocation['used']) - 1) < 0 ) {
                                    $connection->rollBack();
                                    $booking_error_count++;
                                    return response()->json(['error' => 'ROOM_FULLY_BOOKED', 'message' => 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name], 400);
                                    // return 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name;
                                }

                                $room_allocation_used[] = $room_allocation['id']; 
                                // Update used column for room allocation per id
                                RoomAllocation::where('id', $room_allocation['id'])
                                                ->increment('used');

                                $room_rate = RoomRate::where('room_type_id', $room_type->id)
                                            ->whereDate('start_datetime', '<=', $date_period->format('Y-m-d'))
                                            ->whereDate('end_datetime', '>=', $date_period->format('Y-m-d'))
                                            ->whereRaw('json_contains(days_interval, \'["'. strtolower(Carbon::parse($date_period)->isoFormat('ddd')) .'"]\')')
                                            // ->whereRaw('json_contains(allowed_roles, \'["'. $request->user()->roles[0]['name'] .'"]\')')
                                            ->whereRaw('json_contains(allowed_roles, \'["Customer"]\')')
                                            ->orderBy('created_at', 'desc')
                                            ->where('status', 'approved')
                                            ->first();

                                if ($room_rate) {
                                    $isDayAllowed = in_array(strtolower(Carbon::parse($date_period)->isoFormat('ddd')), $room_rate->days_interval);
                                    $isDayExcluded = in_array($date_period->format('Y-m-d'), $room_rate->exclude_days);
                                }

                                $selling_rate = $room_type->rack_rate;

                                if ($room_rate && $isDayAllowed == true && $isDayExcluded == false) {
                                    $selling_rate = $room_rate->room_rate;
                                }
                                
                                if ($last_room_rate == null) {
                                    $last_room_rate = $selling_rate;
                                }

                                // Check if how many nights has the same room rate
                                if ($selling_rate == $last_room_rate) {
                                    // $rooms_to_save_as_inclusions[$batch][][$last_room_rate][] = $date_period->format('Y-m-d');
                                    $rooms_to_save_as_inclusions[$batch]['rate'] = $last_room_rate;
                                    $rooms_to_save_as_inclusions[$batch]['dates'][] = $date_period->format('Y-m-d');
                                } else {
                                    $batch = $batch + 1;
                                    $rooms_to_save_as_inclusions[$batch]['rate'] = $selling_rate;
                                    $rooms_to_save_as_inclusions[$batch]['dates'][] = $date_period->format('Y-m-d');
                                }

                                $last_room_rate = $selling_rate;

                            }
                        }

                        // return $rooms_to_save_as_inclusions;
                        // Update room reservation with the allocations used
                        RoomReservation::where('id', $newRoomReservation->id)
                        ->update([
                            'allocation_used' => json_encode($room_allocation_used)
                        ]);

                        foreach ($rooms_to_save_as_inclusions as $key => $room_rate_data) {

                            // return ($room_rate_data['dates']);

                            $per_booking_room_types_to_save[] = new Inclusion([
                                'invoice_id' => isset($newBooking->invoices[0]) ? $newBooking->invoices[0]->id : 0,
                                'guest_id' => null,
                                // 'guest_id' => $guest['id'],
                                // 'guest_reference_number' => $guest['reference_number'],
                                'item' => "(".$room_type->property->name.") ".$room_type->name,
                                'code' => $room_type->property->code."-".$room_type->code."_".count($room_rate_data['dates'])."NIGHTS_".$room_rate_data['dates'][0]."-to-".end($room_rate_data['dates']),
                                // 'code' => $room_type->property->code."-".$room_type->code."_".$room_reservation_start_datetime->format('Y-m-d_Hi')."-".$room_reservation_end_datetime->format('Y-m-d_Hi'),
                                'type' => 'room_reservation',
                                'description' => null,
                                'serving_time' => null,
                                'used_at' => null,
                                'quantity' => count($room_rate_data['dates']),
                                'original_price' => $room_type->rack_rate, // update this
                                'price' => $room_type->rack_rate, // update this
                                'walkin_price' => 0,
                                'selling_price' => 0,
                                'discount' => null,
                                'created_by' => null,
                            ]);

                            // Update invoice total cost
                            $invoice_total_cost = $invoice_total_cost + ($room_rate_data['rate'] * count($room_rate_data['dates'])); //

                        }

                    }

                }

            }

            $newBooking->inclusions()->saveMany($per_booking_room_types_to_save);


        }

        /**
         * Check if an overnight booking has room reservation
         */ 

        $checkIfHasRoomReservation = Booking::where('id', $newBooking->id)
                                            ->has('room_reservations')
                                            ->first();

        if (!$checkIfHasRoomReservation && $booking_type2 == 'ON') {
            $connection->rollBack();
            return response()->json(['error' => 'OVERNIGHT_BOOKING_WITH_NO_ROOM', 'message' => 'No room available for this package.'], 400);
        }

        /**
         * Check if Camaya Transport booking has Tickets and Passes
         * Feb 21, 2022
         */
        $checkIfBookingHasFerry = Trip::where('booking_reference_number', $newBooking->reference_number)->first();

        if (!$checkIfBookingHasFerry && $newBooking->mode_of_transportation == 'camaya_transportation') {
            $connection->rollBack();
            return response()->json(['error' => 'BOOKING_ERROR_CAMAYA_TRANSPORT_DID_NOT_PROCEED', 'message' => 'There was an error in your booking. Camaya Transportation failed to book.'], 400);
        }

        //

        /**
         * Update invoice status, total_cost, grand_total and balance
         */
        if (isset($newBooking->invoices[0])) {
            Invoice::where('id', $newBooking->invoices[0]->id)->update([
                'status' => 'sent',
                'total_cost' => $invoice_total_cost,
                'grand_total' => $invoice_total_cost,
                'balance' => $invoice_total_cost,
            ]);
        }

        // switch ($request->modeOfPayment) {
        //     case 'dragon_pay':
        //     break;

        //     case 'paypal':

        //     break;
        // }

        if ($booking_error_count == 0) {
            $connection->commit();
        }

        // Mail
        
        $booking = Booking::where('reference_number', $newBooking->reference_number)
                ->with('bookedBy')
                ->with('customer')
                ->with(['guests' => function ($q) {
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
                
        // Mail::to($request->email)->send(new NewBooking($booking));
        Mail::to($request->email)
            // CC additional emails
            ->cc($request->additional_emails)
            ->send(new NewBooking($booking, $camaya_transportations));


        return response()->json($booking, 200);
    }
}
