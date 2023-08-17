<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;
use App\Models\Booking\Inclusion;
use App\Models\Booking\ActivityLog;
use App\Models\Booking\Invoice;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\AutoGate\Pass;
use App\Models\Booking\Stub;
use App\Models\Booking\Setting;

use App\Models\Transportation\SeatSegment;
use App\Models\Transportation\Trip;
use App\Models\Transportation\Seat;
use App\Models\Transportation\Passenger;

use Carbon\Carbon;

class AddFerryToGuests extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
        // return $request->all();

        ///////// BEGIN TRANSACTION //////////
        $connection = DB::connection('camaya_booking_db');
        $connection->beginTransaction();

        $booking = Booking::where('reference_number', $request->booking_reference_number)->first();

        $trip_kid_max = Setting::where('code', 'TRIP_KID_MAX')->first()->value;
        $trip_infant_max = Setting::where('code', 'TRIP_INFANT_MAX')->first()->value;
        $trip_adult_max = Setting::where('code', 'TRIP_ADULT_MAX')->first()->value;

        $infant_min_age = 0;
        $infant_max_age = 2;

        $kid_min_age = 3;
        $kid_max_age = 11;

        $adult_min_age = 12;
        $adult_max_age = 100;

        if (!$booking) {
            return response()->json(['error' => 'BOOKING_NOT_FOUND'], 404);
        }

        $arrival_date = Carbon::parse(date('Y-m-d', strtotime($booking->start_datetime)))->setTimezone('Asia/Manila');
        $departure_date = Carbon::parse(date('Y-m-d', strtotime($booking->end_datetime)))->setTimezone('Asia/Manila');

        $booking->load(['guests' => function ($query) use ($request) {

            $guest_ids = collect($request->guests)->where('selected', true)->pluck('id')->all();

            $query->whereIn('id', $guest_ids);
            $query->with(['tripBookings' => function ($q) {
                $q->whereNotIn('status', ['cancelled', 'no_show']);
            }]);
        }]);

        // $booking->load(['adultGuests' => function ($query) {
        //     $query->whereNotIn('status', ['no_show']);
        // }]);

        // $booking->load(['kidGuests' => function ($query) {
        //     $query->whereNotIn('status', ['no_show']);
        // }]);

        // Adult guests & Kid guests
        // $guests = collect($booking['adultGuests'])->merge($booking['kidGuests'])->all();
        $guests = $booking['guests'];

        // return $guests;

        if ($request->first_trip || $request->second_trip) {

            $invoice_total_cost = null;
            $invoice_grand_total = null;
            $invoice_balance = null;

            $saved_trips = 0;

            $generateInvoiceNumber = "C-".Str::padLeft($booking->id, 7, '0');

            $lastInvoice = Invoice::where('booking_reference_number', $booking->reference_number)
                        ->orderBy('created_at', 'desc')
                        ->first();

            $newInvoice = Invoice::create([
                            'booking_reference_number' => $booking->reference_number,
                            'reference_number' => $generateInvoiceNumber,
                            // 'batch_number' => 0,
                            'batch_number' => $lastInvoice->batch_number + 1, // Increment batch number
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
                            'created_by' => $request->user()->id,
                            'deleted_by' => null,
                        ]);

            $adult_count = 0;
            $kid_count = 0;
            $infant_count = 0;


            foreach ($guests as $key => $guest) {
                if ($guest['age'] >= $adult_min_age) {
                    $adult_count++;
                } else if ($guest['age'] >= $kid_min_age && $guest['age'] <= $kid_max_age ) {
                    $kid_count++;
                } else if ($guest['age'] >= $infant_min_age && $guest['age'] <= $infant_max_age ) {
                    $infant_count++;
                }
            }

            /**
             * Check infant/kid for ferry allocation
             */
            if ($request->first_trip) { 
                // Check infant count Nov 10, 2021
                if ($infant_count > 0 || $kid_count > 0 || $adult_count > 0) {

                    $passengers_age = \App\Models\Transportation\Passenger::where('passengers.trip_number', $request->first_trip['trip_number'])
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
                        return response()->json(['error' => 'FIRST_TRIP_FULLY_BOOKED_INFANT', 'message' => 'Sorry, we reached the maximum no. of infants allowed to board on the first trip.'], 400);
                    }

                    if ($kid_count > $available_slot_for_kid) {
                        $connection->rollBack();
                        return response()->json(['error' => 'FIRST_TRIP_FULLY_BOOKED_KID', 'message' => 'Sorry, we reached the maximum no. of kids allowed to board on the first trip.'], 400);
                    }

                    if ($adult_count > $available_slot_for_adult) {
                        $connection->rollBack();
                        // $booking_error_count++;
                        return response()->json(['error' => 'FIRST_TRIP_FULLY_BOOKED_ADULT', 'message' => 'Sorry, we reached the maximum no. of adults allowed to board on the first trip.'], 400);
                    }

                    if ($request->second_trip) {

                        $passengers_age = \App\Models\Transportation\Passenger::where('passengers.trip_number', $request->second_trip['trip_number'])
                                                ->join('bookings', 'passengers.booking_reference_number', '=', 'bookings.reference_number')
                                                ->join('trips', 'passengers.id', '=', 'trips.passenger_id')
                                                ->whereIn('bookings.status', ['pending', 'confirmed'])
                                                ->whereIn('trips.status', ['boarded', 'checked_in', 'pending'])
                                                // ->where('passengers.age', '<=', 14)
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
                            return response()->json(['error' => 'SECOND_TRIP_FULLY_BOOKED_INFANT', 'message' => 'Sorry, we reached the maximum no. of infants allowed to board on the second trip.'], 400);
                        }

                        if ($kid_count > $available_slot_for_kid) {
                            $connection->rollBack();
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

            foreach ($guests as $guest) {

                $guest_trip_numbers = collect($guest['tripBookings'])->pluck('trip_number')->all();

                if ($request->first_trip) {
                    if (!in_array($request->first_trip['trip_number'], $guest_trip_numbers)) {

                        $saved_trips++;

                        $seat_segment_1 = SeatSegment::where('id', $request->first_trip['id'])
                                                ->with(['schedule' => function ($q) {
                                                    // $q->with('transportation');
                                                }])
                                                ->first();

                        $available_seat_1 = $seat_segment_1->allocated - $seat_segment_1->used;

                        if ($available_seat_1 <= 0) {
                            $connection->rollBack();
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
                            return response()->json(['error' => 'NO_MORE_SEAT_AVAILABLE_1', 'message' => 'No more seat available'], 400);
                        }

                        /**
                         * Increment used allocation on segment
                         */
                        if ($guest['type'] != 'infant') {
                            SeatSegment::where('id', $seat_segment_1['id'])
                                        ->increment('used');
                        }

                        // Create passenger record
                        $newPassenger_1 = Passenger::create([
                            'trip_number' => $seat_segment_1['trip_number'],
                            'booking_reference_number' => $booking['reference_number'],
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
                            'booking_reference_number' => $booking['reference_number'],
                            'passenger_id' => $newPassenger_1->id,
                            'seat_number' => ($guest['type'] != 'infant') ? $seat_1->number : null,
                            'status' => $booking->status == 'confirmed' ? 'checked_in' : 'pending',
                            'seat_segment_id' => $seat_segment_1['id'],
                            'printed' => 0,
                            'last_printed_at' => null,
                            'checked_in_at' => $booking->status == 'confirmed' ? Carbon::now() : null,
                            'boarded_at' => null,
                            'cancelled_at' => null,
                            'no_show_at' => null,
                        ]);

                        // Create inclusion for camaya transportation ticket
                        // Increment invoice total cost
                        $ticket_original_price = ($guest['age'] <= $infant_max_age) ? 0 : $seat_segment_1->rate; // Change this to actual ticket price
                        // $ticket_selling_price = collect($request->selectedPackages)->firstWhere('camaya_transportation_available', true) ? 0 : $ticket_original_price;
                        $ticket_selling_price = $ticket_original_price;
                        $invoice_total_cost = $invoice_total_cost + $ticket_selling_price;

                        $inclusion1 = Inclusion::create([
                            'booking_reference_number' => $booking->reference_number,
                            'invoice_id' => $newInvoice->id,
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
                            'created_by' => $request->user()->id,
                        ]);

                        // Create Passes
                        $schedule_datetime_1 = $seat_segment_1->schedule->trip_date." ".$seat_segment_1->schedule->start_time;
                        $boarding_time_1 = Carbon::parse($schedule_datetime_1)->subHours(1)->format('H:i:s'); // 1 hours before departure
                        $boarding_time_expires_1 = Carbon::parse($schedule_datetime_1)->addMinutes(30)->format('H:i:s'); // 30 minutes after departure
                        Pass::createBoardingPass(
                            $booking->reference_number,
                            $guest['reference_number'],
                            $newTrip_1->id,
                            $seat_segment_1['trip_number'],
                            ($guest['type'] != 'infant') ? $seat_1->number : 'Infant',
                            $seat_segment_1->schedule->trip_date,
                            $boarding_time_1,
                            $boarding_time_expires_1,
                            $inclusion1->id
                        );

                        //Create Product Pass for FTT Entry

                        $entryOrExit = $request->first_trip['destination_code'] === 'EST' ? 'Exit' : 'Entry';

                        $ftt_entry_stub = Stub::where('type', "FTT Pass ".$entryOrExit)->first();
                        Pass::createProductPasses($ftt_entry_stub['id'], $booking->reference_number, $guest['reference_number'], $arrival_date, $departure_date, null);
                    }
                }

                
                if ($request->second_trip) {

                    if (!in_array($request->second_trip['trip_number'], $guest_trip_numbers)) {

                        $saved_trips++;

                        $seat_segment_2 = SeatSegment::where('id', $request->second_trip['id'])
                                            ->with(['schedule' => function ($q) {
                                                // $q->with('transportation');
                                            }])
                                            ->first();

                        $available_seat_2 = $seat_segment_2->allocated - $seat_segment_2->used;

                        if ($available_seat_2 <= 0) {
                            $connection->rollBack();
                            return response()->json(['error' => 'SECOND_TRIP_FULL_BOOKED', 'message' => 'Second trip is fully booked.'], 400);
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
                            return response()->json(['error' => 'NO_MORE_SEAT_AVAILABLE_2', 'message' => 'No more seat available'], 400);
                        }

                        /**
                         * Increment used allocation on segment
                         */
                        if ($guest['type'] != 'infant') {
                            SeatSegment::where('id', $seat_segment_2['id'])
                                        ->increment('used');
                        }

                        // Create passenger record
                        $newPassenger_2 = Passenger::create([
                            'trip_number' => $seat_segment_2['trip_number'],
                            'booking_reference_number' => $booking['reference_number'],
                            'guest_reference_number' => $guest['reference_number'],
                            'first_name' => $guest['first_name'],
                            'last_name' => $guest['last_name'],
                            'age' => $guest['age'],
                            'nationality' => $guest['nationality'] ?? null,
                            'type' => $guest['type'],
                        ]);

                        // Create 1st trip record
                        $newTrip_2 = Trip::create([
                            'trip_number' => $seat_segment_2['trip_number'],
                            'ticket_reference_number' => 1,
                            'guest_reference_number' => $guest['reference_number'],
                            'booking_reference_number' => $booking['reference_number'],
                            'passenger_id' => $newPassenger_2->id,
                            'seat_number' => ($guest['type'] != 'infant') ? $seat_2->number : null,
                            'status' => $booking->status == 'confirmed' ? 'checked_in' : 'pending',
                            'seat_segment_id' => $seat_segment_2['id'],
                            'printed' => 0,
                            'last_printed_at' => null,
                            'checked_in_at' => $booking->status == 'confirmed' ? Carbon::now() : null,
                            'boarded_at' => null,
                            'cancelled_at' => null,
                            'no_show_at' => null,
                        ]);

                        // Create inclusion for camaya transportation ticket
                        // Increment invoice total cost
                        $ticket_original_price = ($guest['age'] <= $infant_max_age) ? 0 : $seat_segment_2->rate; // Change this to actual ticket price
                        // $ticket_selling_price = collect($request->selectedPackages)->firstWhere('camaya_transportation_available', true) ? 0 : $ticket_original_price;
                        $ticket_selling_price = $ticket_original_price;
                        $invoice_total_cost = $invoice_total_cost + $ticket_selling_price;

                        $inclusion2 = Inclusion::create([
                            'booking_reference_number' => $booking->reference_number,
                            'invoice_id' => $newInvoice->id,
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
                            'created_by' => $request->user()->id,
                        ]);

                        // Create Passes
                        $schedule_datetime_2 = $seat_segment_2->schedule->trip_date." ".$seat_segment_2->schedule->start_time;
                        $boarding_time_2 = Carbon::parse($schedule_datetime_2)->subHours(1)->format('H:i:s'); // 1 hours before departure
                        $boarding_time_expires_2 = Carbon::parse($schedule_datetime_2)->addMinutes(30)->format('H:i:s'); // 30 minutes after departure
                        Pass::createBoardingPass(
                            $booking->reference_number,
                            $guest['reference_number'],
                            $newTrip_2->id,
                            $seat_segment_2['trip_number'],
                            ($guest['type'] != 'infant') ? $seat_2->number : 'Infant',
                            $seat_segment_2->schedule->trip_date,
                            $boarding_time_2,
                            $boarding_time_expires_2,
                            $inclusion2->id
                        );

                        //Create Product Pass for FTT Exit
                        $ftt_entry_stub = Stub::where('type', "FTT Pass Exit")->first();
                        Pass::createProductPasses($ftt_entry_stub['id'], $booking->reference_number, $guest['reference_number'], $arrival_date, $departure_date, null);
                    }

                }
            
            }

            // Update new invoice
            /**
             * Update invoice
             */


            if (isset($newInvoice)) {
                if ($saved_trips > 0) {
                    
                    $updatedInvoice = Invoice::where('id', $newInvoice->id)->update([
                        'status' => 'sent',
                        'total_cost' => $invoice_total_cost,
                        'grand_total' => $invoice_total_cost,
                        'balance' => $invoice_total_cost,
                    ]);

                } else {
                    $updatedInvoice = Invoice::where('id', $newInvoice->id)->update([
                        'status' => 'void',
                        'total_cost' => 0,
                        'grand_total' => 0,
                        'balance' => 0,
                    ]);
                }

                $newInvoice->refresh();

            }
            

            // Change booking mode_of_transportation to 'camaya_transportation'
            Booking::where('reference_number', $booking['reference_number'])
                    ->update([
                        'mode_of_transportation' => 'camaya_transportation'
                    ]);

            // Activity log
            // Create log
            ActivityLog::create([
                'booking_reference_number' => $booking['reference_number'],

                'action' => 'add_ferry_to_booking',
                'description' => $request->user()->first_name.' '.$request->user()->last_name.' has added ferry transportation to guests.',
                'model' => 'App\Models\Booking\Booking',
                'model_id' => $booking->id,
                'properties' => null,

                'created_by' => $request->user()->id,
            ]);

            $connection->commit();

            return response()->json(['message' => 'OK'], 200);

        }

    }
}
