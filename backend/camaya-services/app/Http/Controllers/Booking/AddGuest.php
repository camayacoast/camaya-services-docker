<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Str;

use App\Http\Requests\Booking\AddGuestRequest;

use App\Models\Booking\Guest;
use App\Models\Booking\Booking;
use App\Models\Booking\ActivityLog;
use App\Models\Booking\Invoice;
use App\Models\Booking\Product;
use App\Models\Booking\Inclusion;
use App\Models\Booking\LandAllocation;
use App\Models\Booking\DailyGuestLimit;

use App\Models\AutoGate\Pass;

use DB;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AddGuest extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(AddGuestRequest $request)
    {
        //
        // return $request->all();

        ///////// BEGIN TRANSACTION //////////
        $connection = DB::connection('camaya_booking_db');
        $connection->beginTransaction();

        /**
         * Generate New Unique Booking Reference Number
         */ 
        $guest_reference_number = "G-".\Str::upper(\Str::random(6));

        $booking = Booking::where('reference_number', $request->booking_reference_number)->first();

        $period = CarbonPeriod::create($booking->start_datetime, $booking->end_datetime);
        $arrival_date = Carbon::parse(date('Y-m-d', strtotime($booking->start_datetime)))->setTimezone('Asia/Manila');
        $departure_date = Carbon::parse(date('Y-m-d', strtotime($booking->end_datetime)))->setTimezone('Asia/Manila');
        $nights = Carbon::parse($arrival_date)->diffInDays($departure_date);

        $booking_dates = [];
        $array_period_dates = [];

        foreach ($period as $date_period) {
            if ($nights >= 1 && $date_period->format('Y-m-d') != $departure_date->format('Y-m-d')) {
                $booking_dates[] = $date_period->format('Y-m-d')." 00:00:00";
            } 
            
            if ($nights == 0) {
                $booking_dates[] = $date_period->format('Y-m-d')." 00:00:00";
            }

            $array_period_dates[] = $date_period->format('Y-m-d');
        }

        // Creates a new reference number if it encounters duplicate
        while (Guest::where('reference_number', $guest_reference_number)->exists()) {
            $guest_reference_number = "G-".\Str::upper(\Str::random(6));
        }

        $type = 'adult';

        $infant_min_age = 0;
        $infant_max_age = 2;

        $kid_min_age = 3;
        $kid_max_age = 11;

        $adult_min_age = 12;
        $adult_max_age = 100;

        if ($request->age >= 0 && $request->age <= 2) {
            $type = 'infant';

            Booking::where('reference_number', $request->booking_reference_number)
                ->increment('infant_pax', 1);
        } else if ($request->age >= $kid_min_age && $request->age <= $kid_max_age) {
            $type = 'kid';

            Booking::where('reference_number', $request->booking_reference_number)
                ->increment('kid_pax', 1);
        } else {
            Booking::where('reference_number', $request->booking_reference_number)
                ->increment('adult_pax', 1);
        }

        /**
         * DGL Daily Guest Limit
         */
        $dgl_category = '';
        switch ($booking['portal']) {
            case 'admin':
                $dgl_category = 'Admin';
                break;
            case 'agent_portal':
                $dgl_category = 'Sales';
                break;
            case 'website':
                $dgl_category = 'Commercial';
                break;
        }

        if (!$dgl_category) {
            $connection->rollBack();
            return response()->json(['error' => 'NO PORTAL ERROR', 'message' => 'No Portal Selected for this Booking.'], 400);
        }


         $check_daily_limit_per_day = DailyGuestLimit::whereIn('date', [$arrival_date->format('Y-m-d')])->where('category', $dgl_category)->get();
        // count($array_period_dates) > 0 ? $array_period_dates : 

        // [ [date: '2023-01-01', category: 'Admin', limit: 100], [date: '2023-01-02', category: 'Admin', limit: 100] ]
        /**
         * Set and Check daily limit per day
         * DGL, Daily Guest Limit
         */
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
                ->where('bookings.portal', 'admin')
                ->whereIn('bookings.status', ['confirmed', 'pending'])
                ->whereIn('guests.status', ['arriving', 'on_premise', 'checked_in'])
                ->whereNull('guests.deleted_at')
                ->where('guests.type','!=','infant')
                ->count();

        $total_pax = $type != 'infant' ? 1 : 0;

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

         /** - */

        $newGuest = Guest::create([
            'booking_reference_number' => $request->booking_reference_number,
            'reference_number' => $guest_reference_number,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'age' => $request->age,
            'nationality' => $request->nationality,
            'type' => $type,
            'status' => 'arriving',
            'created_by' => $request->user()->id,
        ]);

        /**
         * GUEST INCLUSIONS
         * */ 

        if ($booking->mode_of_transportation != 'camaya_transportation' && $booking->sales_director_id) {
            $land_allocations = LandAllocation::whereIn('date', $booking_dates)
                                            ->where('owner_id', $booking->sales_director_id)
                                            ->where('entity', 'RE')
                                            ->where('status', 'approved')
                                            ->get();
            
            // return $land_allocations;

            if (count($land_allocations) == 0) {
                $connection->rollBack();
                return response()->json(['error' => 'LAND_ALLOCATION', 'message' => 'Sorry, we reached the maximum limit of land allocation.'], 400);
            }
            
            foreach ($land_allocations as $land_allocation) {
                $available = ($land_allocation['allocation'] - $land_allocation['used']) - 1;

                // return $available;

                if ($available < 0) {
                    $connection->rollBack();
                        return response()->json(['error' => 'LAND_ALLOCATION', 'message' => 'Sorry, we reached the maximum limit of land allocation.'], 400);
                }

                LandAllocation::where('id', $land_allocation['id'])->increment('used', 1);

            }
        }

        // return $booking;

        $invoice_total_cost = null;
        $invoice_grand_total = null;
        $invoice_balance = null;

        $generateInvoiceNumber = "C-".Str::padLeft($booking->id, 7, '0');

        $lastInvoice = Invoice::where('booking_reference_number', $booking->reference_number)
                    ->orderBy('created_at', 'desc')
                    ->first();

        $booking->invoices()->create([
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

        /**
         * Save new inclusions (per booking type)
         */

        $products = Product::whereIn('code', collect($request->addGuestInclusions)->pluck('code')->all())->with('productPass')->get();
        $newlyCreatedInvoice = Invoice::where('reference_number', $generateInvoiceNumber)
                                        ->where('batch_number', $lastInvoice->batch_number + 1)
                                        ->first();

        $arrival_date = Carbon::parse(date('Y-m-d', strtotime($booking->start_datetime)))->setTimezone('Asia/Manila');
        $departure_date = Carbon::parse(date('Y-m-d', strtotime($booking->end_datetime)))->setTimezone('Asia/Manila');
        

        // $per_booking_inclusions_to_save = [];

        // foreach ($products as $item) {

        //         if ($item->type == 'per_booking') {

        //             $inclusion = collect($request->inclusions)->firstWhere('id', $item['id']);

        //             $price = $item['price'];

        //             if ($request->addGuestWalkin) {
        //                 $price = ($item['walkin_price'] == null) ? $item['price'] : $item['walkin_price'];
        //             }

        //             // Increment invoice total cost
        //             if (!$inclusion['quantity']) {
        //                 $invoice_total_cost = $invoice_total_cost + $price;
        //             } else {
        //                 $invoice_total_cost = $invoice_total_cost + $price * $inclusion['quantity'];
        //             }

        //             $per_booking_inclusions_to_save[] = new Inclusion([
        //                 'invoice_id' => $newlyCreatedInvoice->id,
        //                 'guest_id' => null,
        //                 'item' => $item['name'],
        //                 'code' => $item['code'],
        //                 'type' => 'product',
        //                 'description' => $item['description'],
        //                 'serving_time' => isset($item['serving_time']) ? Carbon::parse($item['serving_time'][0])->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
        //                 'used_at' => null,
        //                 'quantity' => $inclusion['quantity'],
        //                 'original_price' => $item['price'],
        //                 'price' => $price,
        //                 'discount' => null,
        //                 'created_by' => $request->user()->id,
        //             ]);
        //         }

        // }

        // if (count($per_booking_inclusions_to_save)) {
        //     $booking->inclusions()->saveMany($per_booking_inclusions_to_save);
        // }


        /**
         * Add per guest booking inclusions and packages
         */

        // $per_guest_inclusions_to_save = [];

        foreach ($products as $item) {

            if ($item->type == 'per_guest') {

                $price = $item['price'];

                if ($newGuest['type'] == 'kid') $price = isset($item['kid_price']) ? $item['kid_price'] : $item['price'];
                if ($newGuest['age'] <= $infant_max_age) $price = isset($item['infant_price']) ? $item['infant_price'] : $item['price'];

                if ($request->addGuestWalkin) {
                    $price = ($item['walkin_price'] == null) ? $item['price'] : $item['walkin_price'];
                }

                $addons_per_product = \App\Models\Booking\Addon::where('code', $item['code'])
                                ->where('status', 'valid')
                                ->where('date', $arrival_date)
                                ->whereHas('booking', function ($q) {
                                    $q->whereNotIn('status', ['cancelled']);
                                })
                                ->count();

                if ($newGuest['age'] > $infant_max_age) {
                    if ($addons_per_product >= $item['quantity_per_day'] && $item['quantity_per_day'] > 0) {

                        $connection->rollBack();
                        return response()->json(['error' => $item['code'].'_FULL', 'message' => $item['name'].' is full.'], 400);

                    }
                }

                // Increment invoice total cost
                $invoice_total_cost = $invoice_total_cost + $price;

                $per_guest_inclusions_inclusion = $booking->inclusions()->create([
                    'invoice_id' => $newlyCreatedInvoice->id,
                    'guest_id' => $newGuest['id'],
                    'guest_reference_number' => $guest_reference_number,
                    'item' => $item['name'],
                    'code' => $item['code'],
                    'type' => 'product',
                    'description' => $item['description'],
                    'serving_time' => isset($item['serving_time']) ? Carbon::parse($item['serving_time'][0])->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
                    'used_at' => null,
                    'quantity' => 1,
                    'original_price' => $item['price'],
                    'price' => $price,
                    'walkin_price' => $item['walkin_price'],
                    'selling_price' => $item['price'],
                    'discount' => null,
                    'created_by' => $request->user()->id,
                ]);

                /**
                 * Addon inventory update
                 */
                if ($item['quantity_per_day'] > 0 && $newGuest['age'] > $infant_max_age) {
                    
                    \App\Models\Booking\Addon::create([
                        'booking_reference_number' => $booking['reference_number'],
                        'guest_reference_number' => $guest_reference_number,
                        'code' => $item['code'],
                        'date' => $booking['start_datetime'],
                        'status' => 'valid',
                        'created_by' => $request->user()->id,
                    ]);

                }

                /**
                 * Create Passes when product has pass stub
                 */
                foreach ($item['productPass'] as $product_pass) {
                    
                    Pass::createProductPasses($product_pass['stub_id'], $booking->reference_number, $guest_reference_number, $arrival_date, $departure_date, $per_guest_inclusions_inclusion->id);

                }

            }
        }
             
        // Land Allocation Decrement
        if ($booking->sales_director_id && $type != 'infant') {
            LandAllocation::where('date', $booking->start_datetime)->where('owner_id', $booking->sales_director_id)
                        ->where('entity', 'RE')
                        ->where('status', 'approved')
                        ->increment('used', 1);
        }

        // if (count($per_guest_inclusions_to_save)) {
        //     $booking->inclusions()->saveMany($per_guest_inclusions_to_save);
        // }


        /**
         * Update invoice
         */


        if (isset($newlyCreatedInvoice)) {

            $updatedInvoice = Invoice::where('id', $newlyCreatedInvoice->id)->update([
                'status' => 'sent',
                'total_cost' => $invoice_total_cost,
                'grand_total' => $invoice_total_cost,
                'balance' => $invoice_total_cost,
            ]);

            $newlyCreatedInvoice->refresh();
        }


        // END GUEST INCLUSIONS

        // Create log
        ActivityLog::create([
            'booking_reference_number' => $request->booking_reference_number,

            'action' => 'add_guest',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has added new guest '. $request->first_name .' '. $request->last_name,
            'model' => 'App\Models\Booking\Guest',
            'model_id' => $newGuest->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);

        $connection->commit();

        return $newGuest;
    }
}
