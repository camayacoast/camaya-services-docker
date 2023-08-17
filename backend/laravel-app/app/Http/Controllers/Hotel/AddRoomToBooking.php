<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\Booking\Booking;
use App\Models\Booking\Inclusion;
use App\Models\Booking\Invoice;
use App\Models\Booking\ActivityLog;

use App\Models\Hotel\RoomReservation;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomRate;
use App\Models\Hotel\RoomType;
use App\Models\Hotel\Room;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AddRoomToBooking extends Controller
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

        $arrival_date = Carbon::parse($booking->start_datetime)->format('Y-m-d'). " 12:00:00";
        $departure_date = Carbon::parse($booking->end_datetime)->format('Y-m-d'). " 11:00:00";

        $period = CarbonPeriod::create(Carbon::parse($booking->start_datetime)->format('Y-m-d'), Carbon::parse($booking->end_datetime)->format('Y-m-d'));

        $room_ids_to_book = collect($request->roomsToAdd)->pluck('room_id')->all();

        // Check if rooms are available per room allocation
        $checkIfRoomIsBooked = RoomReservation::where(function ($query) use ($arrival_date, $departure_date) {
                                        $query->where(function ($query) use ($arrival_date, $departure_date) {
                                            $query->where('start_datetime', '<=', $arrival_date)
                                                ->where('end_datetime', '>=', $arrival_date);
                                        })->orWhere(function ($query) use ($arrival_date, $departure_date) {
                                            $query->where('start_datetime', '<=', $departure_date)
                                                ->where('end_datetime', '>=', $departure_date);
                                        })->orWhere(function ($query) use ($arrival_date, $departure_date) {
                                            $query->where('start_datetime', '>=', $arrival_date)
                                                ->where('end_datetime', '<=', $departure_date);
                                        });
                                    })
                                    ->whereIn('status', ['confirmed', 'pending', 'checked_in', 'checked_out', 'blackout'])
                                    ->whereIn('room_id', $room_ids_to_book)
                                    ->get();

        // return $checkIfRoomIsBooked;

        if (count($checkIfRoomIsBooked)) {
            return response()->json(['error' => 'One of the room is already booked. Please refresh the page.', 'booked_rooms' => $checkIfRoomIsBooked], 400);
        }

        /**
         * Book the room(s)
         */

        $invoice_total_cost = null;
        $invoice_grand_total = null;
        $invoice_balance = null;

        $generateInvoiceNumber = "C-".Str::padLeft($booking->id, 7, '0');

        $lastInvoice = Invoice::where('booking_reference_number', $booking->reference_number)
                    ->orderBy('created_at', 'desc')
                    ->first();

        // Create invoice
        $newInvoice = Invoice::create([
            'booking_reference_number' => $booking->reference_number,
            'reference_number' => $generateInvoiceNumber,
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

        foreach ($request->roomsToAdd as $room_to_add) {

            $room_reservation_start_datetime = Carbon::parse(date('Y-m-d', strtotime($arrival_date)))->setTimezone('Asia/Manila');
            $room_reservation_start_datetime->hour = 12;
            $room_reservation_start_datetime->minute = 00;

            $room_reservation_end_datetime = Carbon::parse(date('Y-m-d', strtotime($departure_date)))->setTimezone('Asia/Manila');
            $room_reservation_end_datetime->hour = 11;
            $room_reservation_end_datetime->minute = 00;

            $newRoomReservation = RoomReservation::create([
                'room_id' => $room_to_add['room_id'],
                'room_type_id' => $room_to_add['room_type_id'],
                'booking_reference_number' => $booking->reference_number,
                'category' => 'booking',
                'status' => 'pending',
                'start_datetime' => $room_reservation_start_datetime,
                'end_datetime' => $room_reservation_end_datetime,
                'created_by' => $request->user()->id
            ]);

            $room_allocation_used = [];

            $rooms_to_save_as_inclusions = [];
            $last_room_rate = null;
            // $room_type_id = 1;
            $batch = 0;

            foreach ($period as $date_period) {
                if ($date_period->format('Y-m-d') != Carbon::parse($departure_date)->format('Y-m-d')) {

                    // Check room allocations if available
                    $room_allocation = RoomAllocation::where('entity', $room_to_add['allocation'])
                                        ->whereDate('date', $date_period->format('Y-m-d'))
                                        ->where('room_type_id', $room_to_add['room_type_id'])
                                        ->where('status', 'approved')
                                        ->first();

                    if (!isset($room_allocation)) {
                        $connection->rollBack();
                        return response()->json(['error' => 'NO_ROOM_ALLOCATION'], 400);
                    }

                    if ( (($room_allocation['allocation'] - $room_allocation['used']) - 1) < 0 ) {
                        $connection->rollBack();
                        return response()->json(['error' => 'ROOM_FULLY_BOOKED_ALA_CARTE_2'], 400);
                        // return 'Fully booked for room type: ('. $room_type->property->name .') '.$room_type->name;
                    }

                    $room_allocation_used[] = $room_allocation['id']; 
                    // Update used column for room allocation per id
                    RoomAllocation::where('id', $room_allocation['id'])
                                    ->increment('used');

                    $room_type = RoomType::where('id', $room_to_add['room_type_id'])->first();

                    $room_rate = RoomRate::where('room_type_id', $room_to_add['room_type_id'])
                                ->whereDate('start_datetime', '<=', $date_period->format('Y-m-d'))
                                ->whereDate('end_datetime', '>=', $date_period->format('Y-m-d'))
                                ->whereRaw('json_contains(days_interval, \'["'. strtolower(Carbon::parse($date_period)->isoFormat('ddd')) .'"]\')')
                                ->whereRaw('json_contains(allowed_roles, \'["'. $request->user()->roles[0]['name'] .'"]\')')
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

                // $per_booking_room_types_to_save[] = new Inclusion([
                Inclusion::create([
                    'booking_reference_number' => $booking->reference_number,
                    'invoice_id' => isset($newInvoice) ? $newInvoice->id : 0,
                    'guest_id' => null,
                    'item' => "(".$room_type->property->name.") ".$room_type->name,
                    'code' => $room_type->property->code."-".$room_type->code."_".count($room_rate_data['dates'])."NIGHTS_".$room_rate_data['dates'][0]."-to-".end($room_rate_data['dates']),
                    // 'code' => $room_type->property->code."-".$room_type->code."_".$room_reservation_start_datetime->format('Y-m-d_Hi')."-".$room_reservation_end_datetime->format('Y-m-d_Hi'),
                    'type' => 'room_reservation',
                    'description' => null,
                    'serving_time' => null,
                    'used_at' => null,
                    'quantity' => count($room_rate_data['dates']),
                    'original_price' => $room_type->rack_rate, // update this
                    'price' => $room_rate_data['rate'], // update this
                    'walkin_price' => 0,
                    'selling_price' => 0,
                    'discount' => null,
                    'created_by' => $request->user()->id,
                ]);

                // Update invoice total cost
                $invoice_total_cost = $invoice_total_cost + ($room_rate_data['rate'] * count($room_rate_data['dates'])); //

            }
 

        }

        // per_booking_room_types_to_save

        /**
         * Update invoice status, total_cost, grand_total and balance
         */
        if (isset($newInvoice)) {
            Invoice::where('id', $newInvoice->id)->update([
                'status' => 'sent',
                'total_cost' => $invoice_total_cost,
                'grand_total' => $invoice_total_cost,
                'balance' => $invoice_total_cost,
            ]);
        }

        $connection->commit();

        $roomsData = Room::whereIn('id', $room_ids_to_book)
                        ->with('type')
                        ->get();

        $roomsDataArray = [];

        foreach ($roomsData as $rd) {
            $roomsDataArray[] = $rd['number'].' ('.$rd['type']['name'].')';
        }

        // Create log
        // use App\Models\Booking\ActivityLog;
        ActivityLog::create([
            'booking_reference_number' => $request->booking_reference_number,

            'action' => 'add_room_to_booking',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has added new room(s) to booking ('.implode(", ",$roomsDataArray).').',
            'model' => 'App\Models\Booking\Booking',
            'model_id' => $booking->id,
            'properties' => json_encode([
                'new_data' => $roomsData,
            ]),

            'created_by' => $request->user()->id,
        ]);

        return "OK";
    }
}
