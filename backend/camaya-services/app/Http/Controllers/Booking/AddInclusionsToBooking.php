<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Str;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingTag;
use App\Models\Booking\Invoice;
use App\Models\Booking\Product;
use App\Models\Booking\Package;
use App\Models\Booking\Inclusion;
use App\Models\Booking\Guest;

use App\Models\AutoGate\Pass;

use DB;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AddInclusionsToBooking extends Controller
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

        $arrival_date = Carbon::parse(date('Y-m-d', strtotime($booking->start_datetime)))->setTimezone('Asia/Manila');
        $departure_date = Carbon::parse(date('Y-m-d', strtotime($booking->end_datetime)))->setTimezone('Asia/Manila');

        $nights = $arrival_date->diffInDays($departure_date);

        $period = CarbonPeriod::create($arrival_date, $departure_date);

        $array_period_dates=[];
        
        foreach ($period as $date_period) {
            if ($date_period->format('Y-m-d') != $departure_date->format('Y-m-d')) {
                $array_period_dates[] = $date_period->format('Y-m-d');
            }
        }

        // Weekday or Weekend
        $dtt_weekday = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $dtt_weekend = ['Saturday', 'Sunday'];
        $ovn_weekday = ['Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        $ovn_weekend = ['Friday','Saturday', 'Sunday'];

        // Check if walkin
        $is_walkin = BookingTag::where('booking_id', $booking->id)->where('name', 'Walkin')->first();
        
        $infant_max_age = 2;

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

        $products = Product::whereIn('id', collect($request->inclusions)->where('inclusion_type', 'product')->pluck('id')->all())->with('productPass')->get();
        $packages = Package::whereIn('id', collect($request->inclusions)->where('inclusion_type', 'package')->pluck('id')->all())->with('packageInclusions.product')->get();
        $newlyCreatedInvoice = Invoice::where('reference_number', $generateInvoiceNumber)
                                        ->where('batch_number', $lastInvoice->batch_number + 1)
                                        ->first();

        $guests = Guest::where('booking_reference_number', $booking->reference_number)->get();

        // $per_guest_inclusions_to_save = [];
        // $per_guest_package_to_save = [];
        

        $per_booking_inclusions_to_save = [];
        // $per_booking_package_to_save = [];

        foreach ($products as $item) {

            if ($item->type == 'per_booking') {

                if ($nights >= 1) {
                    for ($i = 0; $i < $nights; $i++) {

                        $inclusion = collect($request->inclusions)->firstWhere('id', $item['id']);

                        $price = $item['price'];
                        $selling_price_type = null;

                        if ($item['weekday_rate'] > 0 && in_array(Carbon::parse($array_period_dates[$i])->format('l'), $ovn_weekday)) {
                            $price = $item['weekday_rate'];
                            $selling_price_type = 'Weekday Rate';
                        } else if ($item['weekend_rate'] > 0 && in_array(Carbon::parse($array_period_dates[$i])->format('l'), $ovn_weekend)) {
                            $price = $item['weekend_rate'];
                            $selling_price_type = 'Weekend Rate';
                        } else { 
                            $price = $item['price'];
                        }

                        if ($is_walkin) {
                            $selling_price_type = 'Walkin Rate';
                            $price = ($item['walkin_price'] == null) ? $price : $item['walkin_price'];
                        }

                        // Increment invoice total cost
                        if (!$inclusion['quantity']) {
                            $invoice_total_cost = $invoice_total_cost + $price;
                        } else {
                            $invoice_total_cost = $invoice_total_cost + $price * $inclusion['quantity'];
                        }

                        $per_booking_inclusions_to_save[] = new Inclusion([
                            'invoice_id' => $newlyCreatedInvoice->id,
                            'guest_id' => null,
                            'item' => $item['name'],
                            'code' => $item['code'],
                            'type' => 'product',
                            'description' => $item['description'],
                            'serving_time' => isset($item['serving_time']) ? Carbon::parse($item['serving_time'][0])->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
                            'used_at' => null,
                            'quantity' => $inclusion['quantity'],
                            'original_price' => $item['price'],
                            'price' => $price,
                            'walkin_price' => $item['walkin_price'],
                            'selling_price' => $price,
                            'selling_price_type' => $selling_price_type,
                            'discount' => null,
                            'created_by' => $request->user()->id,
                        ]);
                    }
                } else {
                        $inclusion = collect($request->inclusions)->firstWhere('id', $item['id']);

                        $price = $item['price'];
                        $selling_price_type = null;

                        if ($item['weekday_rate'] > 0 && in_array(Carbon::parse($arrival_date)->format('l'), $dtt_weekday)) {
                            $price = $item['weekday_rate'];
                            $selling_price_type = 'Weekday Rate';
                        } else if ($item['weekend_rate'] > 0 && in_array(Carbon::parse($arrival_date)->format('l'), $dtt_weekend)) {
                            $price = $item['weekend_rate'];
                            $selling_price_type = 'Weekend Rate';
                        } else { 
                            $price = $item['price'];
                        }

                        if ($is_walkin) {
                            $selling_price_type = 'Walkin Rate';
                            $price = ($item['walkin_price'] == null) ? $price : $item['walkin_price'];
                        }

                        // Increment invoice total cost
                        if (!$inclusion['quantity']) {
                            $invoice_total_cost = $invoice_total_cost + $price;
                        } else {
                            $invoice_total_cost = $invoice_total_cost + $price * $inclusion['quantity'];
                        }

                        $per_booking_inclusions_to_save[] = new Inclusion([
                            'invoice_id' => $newlyCreatedInvoice->id,
                            'guest_id' => null,
                            'item' => $item['name'],
                            'code' => $item['code'],
                            'type' => 'product',
                            'description' => $item['description'],
                            'serving_time' => isset($item['serving_time']) ? Carbon::parse($item['serving_time'][0])->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
                            'used_at' => null,
                            'quantity' => $inclusion['quantity'],
                            'original_price' => $item['price'],
                            'price' => $price,
                            'walkin_price' => $item['walkin_price'],
                            'selling_price' => $price,
                            'selling_price_type' => $selling_price_type,
                            'discount' => null,
                            'created_by' => $request->user()->id,
                        ]);
                }
                // end of ($item->type == 'per_booking')
            }

        }

        /**
         * Package inclusions
         */

        if ($packages) {
            foreach ($packages as $package) {
                if ($package->type == 'per_booking') {

                    if ($nights >= 1) {
                        for ($i = 0; $i < $nights; $i++) {
    
                            $price = $package['selling_price'];
                            $selling_price_type = null;
    
                            if ($package['weekday_rate'] > 0 && in_array(Carbon::parse($array_period_dates[$i])->format('l'), $ovn_weekday)) {
                                $price = $package['weekday_rate'];
                                $selling_price_type = 'Weekday Rate';
                            } else if ($package['weekend_rate'] > 0 && in_array(Carbon::parse($array_period_dates[$i])->format('l'), $ovn_weekend)) {
                                $price = $package['weekend_rate'];
                                $selling_price_type = 'Weekend Rate';
                            } else { 
                                $price = $package['selling_price'];
                            }
    
                            if ($is_walkin) {
                                $selling_price_type = 'Walkin Rate';
                                $price = ($package['walkin_price'] == null) ? $price : $package['walkin_price'];
                            }
            
                            // Increment invoice total cost
                            if (!$package['quantity']) {
                                $invoice_total_cost = $invoice_total_cost + $price;
                            } else {
                                $invoice_total_cost = $invoice_total_cost + ($price * $package['quantity']);
                            }
            
                            $per_booking_package_save = $booking->inclusions()->create([
                                'invoice_id' => $newlyCreatedInvoice->id,
                                'guest_id' => null,
                                'item' => $package['name'],
                                'code' => $package['code'],
                                'type' => 'package',
                                'description' => null,
                                'serving_time' => null,
                                'used_at' => null,
                                'quantity' => $package['quantity'] ?? 1,
                                'original_price' => $package['regular_price'],
                                'price' => $price,
                                'walkin_price' => $package['walkin_price'],
                                'selling_price' => $price,
                                'selling_price_type' => $selling_price_type,
                                'discount' => null,
                                'created_by' => $request->user()->id,
                            ]);

                            foreach ($package->packageInclusions as $package_inclusions) {
                                $package_inclusion_product = Product::where('code', $package_inclusions['product']['code'])->with('productPass')->first();

                                if ($package_inclusions['product']['type'] == 'per_guest') {
                                    foreach ($guests as $guest) {
                                            $per_booking_package_inclusion = $booking->inclusions()->create([
                                                'invoice_id' => $newlyCreatedInvoice->id,
                                                'guest_id' => $guest['id'],
                                                'guest_reference_number' => $guest['reference_number'],
                                                'parent_id' => $per_booking_package_save->id,
                                                'item' => $package_inclusions['product']['name'],
                                                'code' => $package_inclusions['product']['code'],
                                                'type' => 'package_inclusion',
                                                // 'description' => null,
                                                // 'serving_time' => null,
                                                // 'used_at' => null,
                                                'quantity' => 1,
                                                'original_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusion_product->price,
                                                'price' => 0,
                                                'walkin_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['walkin_price'],
                                                'selling_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['price'],
                                                // 'discount' => null,
                                                // 'created_by' => $request->user()->id,
                                            ]);

                                            // Check if add exceeds daily limit
                                            $addons = \App\Models\Booking\Addon::where('code', $package_inclusion_product['code'])
                                                    ->where('status', 'valid')
                                                    ->where('date', Carbon::parse($array_period_dates[$i])->setTimezone('Asia/Manila'))
                                                    ->whereHas('booking', function ($q) {
                                                        $q->whereNotIn('status', ['cancelled']);
                                                    })
                                                    ->count();

                                            if ($addons >= $package_inclusion_product['quantity_per_day'] && $package_inclusion_product['quantity_per_day'] > 0) {

                                                $connection->rollBack();
                                                return response()->json(['error' => $package_inclusion_product['code'].'_FULL', 'message' => $package_inclusion_product['name'].' is full.'], 400);

                                            }

                                            /**
                                             * Addon inventory update
                                             */
                                            if ($package_inclusion_product['quantity_per_day'] > 0 && $guest['age'] > $infant_max_age) {
                                                
                                                \App\Models\Booking\Addon::create([
                                                    'booking_reference_number' => $booking['reference_number'],
                                                    'guest_reference_number' => $guest['reference_number'],
                                                    'code' => $package_inclusion_product['code'],
                                                    'date' => Carbon::parse($array_period_dates[$i])->setTimezone('Asia/Manila'),
                                                    'status' => 'valid',
                                                    'created_by' => $request->user()->id,
                                                ]);
                                            }

                                            /**
                                             * Create Passes when product has pass stub
                                             */
                                            foreach ($package_inclusion_product['productPass'] as $product_pass) {

                                                Pass::createProductPasses($product_pass['stub_id'], $booking->reference_number, $guest['reference_number'], $arrival_date, $departure_date, $per_booking_package_inclusion->id);

                                            }
                                        }
                                    }

                                if ($package_inclusions['product']['type'] == 'per_booking') {
                                    $per_booking_package_inclusion = $booking->inclusions()->create([
                                        'invoice_id' => $newlyCreatedInvoice->id,
                                        // 'guest_id' => $guest['id'],
                                        // 'guest_reference_number' => $guest['reference_number'],
                                        'parent_id' => $per_booking_package_save->id,
                                        'item' => $package_inclusions['product']['name'],
                                        'code' => $package_inclusions['product']['code'],
                                        'type' => 'package_inclusion',
                                        // 'description' => null,
                                        // 'serving_time' => null,
                                        // 'used_at' => null,
                                        'quantity' => $package_inclusions['quantity'],
                                        'original_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusion_product->price,
                                        'price' => 0,
                                        'walkin_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['walkin_price'],
                                        'selling_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['price'],
                                        // 'discount' => null,
                                        // 'created_by' => $request->user()->id,
                                    ]);

                                }

                                
                            }
                        }
                    } else {
                            $price = $package['selling_price'];
                            $selling_price_type = null;
    
                            if ($package['weekday_rate'] > 0 && in_array(Carbon::parse($arrival_date)->format('l'), $dtt_weekday)) {
                                $price = $package['weekday_rate'];
                                $selling_price_type = 'Weekday Rate';
                            } else if ($package['weekend_rate'] > 0 && in_array(Carbon::parse($arrival_date)->format('l'), $dtt_weekend)) {
                                $price = $package['weekend_rate'];
                                $selling_price_type = 'Weekend Rate';
                            } else { 
                                $price = $package['selling_price'];
                            }
    
                            if ($is_walkin) {
                                $selling_price_type = 'Walkin Rate';
                                $price = ($package['walkin_price'] == null) ? $price : $package['walkin_price'];
                            }
            
                            // Increment invoice total cost
                            if (!$package['quantity']) {
                                $invoice_total_cost = $invoice_total_cost + $price;
                            } else {
                                $invoice_total_cost = $invoice_total_cost + ($price * $package['quantity']);
                            }
            
                            $per_booking_package_save = $booking->inclusions()->create([
                                'invoice_id' => $newlyCreatedInvoice->id,
                                'guest_id' => null,
                                'item' => $package['name'],
                                'code' => $package['code'],
                                'type' => 'package',
                                'description' => null,
                                'serving_time' => null,
                                'used_at' => null,
                                'quantity' => $package['quantity'] ?? 1,
                                'original_price' => $package['regular_price'],
                                'price' => $price,
                                'walkin_price' => $package['walkin_price'],
                                'selling_price' => $price,
                                'selling_price_type' => $selling_price_type,
                                'discount' => null,
                                'created_by' => $request->user()->id,
                            ]);

                            foreach ($package->packageInclusions as $package_inclusions) {
                                $package_inclusion_product = Product::where('code', $package_inclusions['product']['code'])->with('productPass')->first();

                                if ($package_inclusions['product']['type'] == 'per_guest') {
                                    foreach ($guests as $guest) {
                                            $per_booking_package_inclusion = $booking->inclusions()->create([
                                                'invoice_id' => $newlyCreatedInvoice->id,
                                                'guest_id' => $guest['id'],
                                                'guest_reference_number' => $guest['reference_number'],
                                                'parent_id' => $per_booking_package_save->id,
                                                'item' => $package_inclusions['product']['name'],
                                                'code' => $package_inclusions['product']['code'],
                                                'type' => 'package_inclusion',
                                                // 'description' => null,
                                                // 'serving_time' => null,
                                                // 'used_at' => null,
                                                'quantity' => 1,
                                                'original_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusion_product->price,
                                                'price' => 0,
                                                'walkin_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['walkin_price'],
                                                'selling_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['price'],
                                                // 'discount' => null,
                                                // 'created_by' => $request->user()->id,
                                            ]);

                                            // Check if add exceeds daily limit
                                            $addons = \App\Models\Booking\Addon::where('code', $package_inclusion_product['code'])
                                                    ->where('status', 'valid')
                                                    ->where('date', $arrival_date)
                                                    ->whereHas('booking', function ($q) {
                                                        $q->whereNotIn('status', ['cancelled']);
                                                    })
                                                    ->count();

                                            if ($addons >= $package_inclusion_product['quantity_per_day'] && $package_inclusion_product['quantity_per_day'] > 0) {

                                                $connection->rollBack();
                                                return response()->json(['error' => $package_inclusion_product['code'].'_FULL', 'message' => $package_inclusion_product['name'].' is full.'], 400);

                                            }

                                            /**
                                             * Addon inventory update
                                             */
                                            if ($package_inclusion_product['quantity_per_day'] > 0 && $guest['age'] > $infant_max_age) {
                                                
                                                \App\Models\Booking\Addon::create([
                                                    'booking_reference_number' => $booking['reference_number'],
                                                    'guest_reference_number' => $guest['reference_number'],
                                                    'code' => $package_inclusion_product['code'],
                                                    'date' => $arrival_date,
                                                    'status' => 'valid',
                                                    'created_by' => $request->user()->id,
                                                ]);
                                            }

                                            /**
                                             * Create Passes when product has pass stub
                                             */
                                            foreach ($package_inclusion_product['productPass'] as $product_pass) {

                                                Pass::createProductPasses($product_pass['stub_id'], $booking->reference_number, $guest['reference_number'], $arrival_date, $departure_date, $per_booking_package_inclusion->id);

                                            }
                                        }
                                    }

                                if ($package_inclusions['product']['type'] == 'per_booking') {
                                    $per_booking_package_inclusion = $booking->inclusions()->create([
                                        'invoice_id' => $newlyCreatedInvoice->id,
                                        // 'guest_id' => $guest['id'],
                                        // 'guest_reference_number' => $guest['reference_number'],
                                        'parent_id' => $per_booking_package_save->id,
                                        'item' => $package_inclusions['product']['name'],
                                        'code' => $package_inclusions['product']['code'],
                                        'type' => 'package_inclusion',
                                        // 'description' => null,
                                        // 'serving_time' => null,
                                        // 'used_at' => null,
                                        'quantity' => $package_inclusions['quantity'],
                                        'original_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusion_product->price,
                                        'price' => 0,
                                        'walkin_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['walkin_price'],
                                        'selling_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['price'],
                                        // 'discount' => null,
                                        // 'created_by' => $request->user()->id,
                                    ]);

                                }

                                
                            }
                    }

                }
            }
        }

        /**
         * Add per guest booking inclusions and packages
         */

        foreach ($guests as $guest) {

            foreach ($products as $item) {

                if ($item->type == 'per_guest') {

                    if ($nights >= 1) {
                        for ($i = 0; $i < $nights; $i++) {

                            $price = $item['price'];
                            
                            $selling_price_type = null;

                            if ($item['weekday_rate'] > 0 && in_array(Carbon::parse($array_period_dates[$i])->format('l'), $ovn_weekday)) {
                                $price = $item['weekday_rate'];
                                $selling_price_type = 'Weekday Rate';
                            } else if ($item['weekend_rate'] > 0 && in_array(Carbon::parse($array_period_dates[$i])->format('l'), $ovn_weekend)) {
                                $price = $item['weekend_rate'];
                                $selling_price_type = 'Weekend Rate';
                            } else { 
                                $price = $item['price'];
                            }

                            if ($guest['type'] == 'kid') $price = isset($item['kid_price']) ? $item['kid_price'] : $item['price'];
                            if ($guest['age'] <= $infant_max_age) {
                                // $price = isset($item['infant_price']) ? $item['infant_price'] : $item['price'];
                                $price = 0;
                            }

                            if ($is_walkin) {
                                $selling_price_type = 'Walkin Rate';
                                $price = ($item['walkin_price'] == null) ? $price : $item['walkin_price'];
                            }

                            // Check if add exceeds daily limit
                            $addons = \App\Models\Booking\Addon::where('code', $item['code'])
                                            ->where('status', 'valid')
                                            ->where('date', Carbon::parse($array_period_dates[$i])->setTimezone('Asia/Manila'))
                                            ->whereHas('booking', function ($q) {
                                                $q->whereNotIn('status', ['cancelled']);
                                            })
                                            ->count();

                            if ($addons >= $item['quantity_per_day'] && $item['quantity_per_day'] > 0) {

                                $connection->rollBack();
                                return response()->json(['error' => $item['code'].'_FULL', 'message' => $item['name'].' is full.'], 400);

                            }

                            // Increment invoice total cost
                            if ($guest['age'] > $infant_max_age) {
                                $invoice_total_cost = $invoice_total_cost + $price;
                            }

                            $per_guest_inclusions_save = $booking->inclusions()->create([
                                'invoice_id' => $newlyCreatedInvoice->id,
                                'guest_id' => $guest['id'],
                                'guest_reference_number' => $guest['reference_number'],
                                'item' => $item['name'],
                                'code' => $item['code'],
                                'type' => 'product',
                                'description' => $item['description'],
                                'serving_time' => isset($item['serving_time']) ? Carbon::parse($item['serving_time'][0])->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
                                'used_at' => null,
                                'quantity' => 1,
                                'original_price' => $guest['age'] <= $infant_max_age ? 0 : $item['price'],
                                // 'price' => $price,
                                'price' => $guest['age'] <= $infant_max_age ? 0 : $price,
                                'walkin_price' => $guest['age'] <= $infant_max_age ? 0 : $item['walkin_price'],
                                'selling_price' => $guest['age'] <= $infant_max_age ? 0 : $price,
                                'selling_price_type' => $selling_price_type,
                                'discount' => null,
                                'created_by' => $request->user()->id,
                            ]);

                            /**
                             * Addon inventory update
                             */
                            if ($item['quantity_per_day'] > 0 && $guest['age'] > $infant_max_age) {
                                
                                \App\Models\Booking\Addon::create([
                                    'booking_reference_number' => $booking['reference_number'],
                                    'guest_reference_number' => $guest['reference_number'],
                                    'code' => $item['code'],
                                    'date' => Carbon::parse($array_period_dates[$i])->setTimezone('Asia/Manila'),
                                    'status' => 'valid',
                                    'created_by' => $request->user()->id,
                                ]);
                            }

                            /**
                             * Create Passes when product has pass stub
                             */
                            foreach ($item['productPass'] as $product_pass) {
                                
                                Pass::createProductPasses($product_pass['stub_id'], $booking->reference_number, $guest['reference_number'], $arrival_date, $departure_date, $per_guest_inclusions_save->id);

                            }
                        }
                    } else {
                            $price = $item['price'];
                            
                            $selling_price_type = null;

                            if ($item['weekday_rate'] > 0 && in_array(Carbon::parse($arrival_date)->format('l'), $dtt_weekday)) {
                                $price = $item['weekday_rate'];
                                $selling_price_type = 'Weekday Rate';
                            } else if ($item['weekend_rate'] > 0 && in_array(Carbon::parse($arrival_date)->format('l'), $dtt_weekend)) {
                                $price = $item['weekend_rate'];
                                $selling_price_type = 'Weekend Rate';
                            } else { 
                                $price = $item['price'];
                            }

                            if ($guest['type'] == 'kid') $price = isset($item['kid_price']) ? $item['kid_price'] : $item['price'];
                            if ($guest['age'] <= $infant_max_age) {
                                // $price = isset($item['infant_price']) ? $item['infant_price'] : $item['price'];
                                $price = 0;
                            }

                            if ($is_walkin) {
                                $selling_price_type = 'Walkin Rate';
                                $price = ($item['walkin_price'] == null) ? $price : $item['walkin_price'];
                            }

                            // Check if add exceeds daily limit
                            $addons = \App\Models\Booking\Addon::where('code', $item['code'])
                                            ->where('status', 'valid')
                                            ->where('date', $arrival_date)
                                            ->whereHas('booking', function ($q) {
                                                $q->whereNotIn('status', ['cancelled']);
                                            })
                                            ->count();

                            if ($addons >= $item['quantity_per_day'] && $item['quantity_per_day'] > 0) {

                                $connection->rollBack();
                                return response()->json(['error' => $item['code'].'_FULL', 'message' => $item['name'].' is full.'], 400);

                            }

                            // Increment invoice total cost
                            if ($guest['age'] > $infant_max_age) {
                                $invoice_total_cost = $invoice_total_cost + $price;
                            }

                            $per_guest_inclusions_save = $booking->inclusions()->create([
                                'invoice_id' => $newlyCreatedInvoice->id,
                                'guest_id' => $guest['id'],
                                'guest_reference_number' => $guest['reference_number'],
                                'item' => $item['name'],
                                'code' => $item['code'],
                                'type' => 'product',
                                'description' => $item['description'],
                                'serving_time' => isset($item['serving_time']) ? Carbon::parse($item['serving_time'][0])->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
                                'used_at' => null,
                                'quantity' => 1,
                                'original_price' => $guest['age'] <= $infant_max_age ? 0 : $item['price'],
                                // 'price' => $price,
                                'price' => $guest['age'] <= $infant_max_age ? 0 : $price,
                                'walkin_price' => $guest['age'] <= $infant_max_age ? 0 : $item['walkin_price'],
                                'selling_price' => $guest['age'] <= $infant_max_age ? 0 : $price,
                                'selling_price_type' => $selling_price_type,
                                'discount' => null,
                                'created_by' => $request->user()->id,
                            ]);

                            /**
                             * Addon inventory update
                             */
                            if ($item['quantity_per_day'] > 0 && $guest['age'] > $infant_max_age) {
                                
                                \App\Models\Booking\Addon::create([
                                    'booking_reference_number' => $booking['reference_number'],
                                    'guest_reference_number' => $guest['reference_number'],
                                    'code' => $item['code'],
                                    'date' => $arrival_date,
                                    'status' => 'valid',
                                    'created_by' => $request->user()->id,
                                ]);
                            }

                            /**
                             * Create Passes when product has pass stub
                             */
                            foreach ($item['productPass'] as $product_pass) {
                                
                                Pass::createProductPasses($product_pass['stub_id'], $booking->reference_number, $guest['reference_number'], $arrival_date, $departure_date, $per_guest_inclusions_save->id);

                            }
                    }
                }
                
            }

            if ($packages) {
                foreach ($packages as $item) {

                    if ($item->type == 'per_guest') {

                        if ($nights >= 1) {
                            for ($i = 0; $i < $nights; $i++) {
    
                                $price = $item['selling_price'];
                                
                                $selling_price_type = null;
    
                                if ($item['weekday_rate'] > 0 && in_array(Carbon::parse($array_period_dates[$i])->format('l'), $ovn_weekday)) {
                                    $price = $item['weekday_rate'];
                                    $selling_price_type = 'Weekday Rate';
                                } else if ($item['weekend_rate'] > 0 && in_array(Carbon::parse($array_period_dates[$i])->format('l'), $ovn_weekend)) {
                                    $price = $item['weekend_rate'];
                                    $selling_price_type = 'Weekend Rate';
                                } else { 
                                    $price = $item['selling_price'];
                                }

                                if ($is_walkin) {
                                    $selling_price_type = 'Walkin Rate';
                                    $price = ($item['walkin_price'] == null) ? $price : $item['walkin_price'];
                                }
            
                                // Increment invoice total cost (if not infant)
                                if ($guest['age'] > $infant_max_age) {
                                    $invoice_total_cost = $invoice_total_cost + $price;
                                }

                                $per_guest_package_save = $booking->inclusions()->create([
                                    'invoice_id' => $newlyCreatedInvoice->id,
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
                                    'selling_price' =>$guest['age'] <= $infant_max_age ? 0 : $price,
                                    'selling_price_type' => $selling_price_type,
                                    'discount' => null,
                                    'created_by' => $request->user()->id,
                                ]);

                                foreach ($item->packageInclusions as $package_inclusions) {
                                    $package_inclusion_product = Product::where('code', $package_inclusions['product']['code'])->with('productPass')->first();

                                    $addons_per_product = \App\Models\Booking\Addon::where('code', $package_inclusion_product['code'])
                                                    ->where('status', 'valid')
                                                    ->where('date', Carbon::parse($array_period_dates[$i])->setTimezone('Asia/Manila'))
                                                    ->whereHas('booking', function ($q) {
                                                        $q->whereNotIn('status', ['cancelled']);
                                                    })
                                                    ->count();

                                    if ($guest['age'] > $infant_max_age) {
                                        if ($addons_per_product >= $package_inclusion_product['quantity_per_day'] && $package_inclusion_product['quantity_per_day'] > 0) {

                                            $connection->rollBack();
                                            return response()->json(['error' => $package_inclusion_product['code'].'_FULL', 'message' => $package_inclusion_product['name'].' is full.'], 400);

                                        }
                                    }
            
                                    if ($package_inclusions['product']['type'] == 'per_guest') {
                                        $per_guest_package_inclusion = $booking->inclusions()->create([
                                            'invoice_id' => $newlyCreatedInvoice->id,
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
                                            'walkin_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['walkin_price'],
                                            'selling_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['price'],
                                            'discount' => null,
                                            'created_by' => $request->user()->id,
                                        ]);
                                    } else if ($package_inclusions['product']['type'] == 'per_booking') {
                                        $per_guest_package_inclusion = $booking->inclusions()->create([
                                            'invoice_id' => $newlyCreatedInvoice->id,
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
                                            'walkin_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['walkin_price'],
                                            'selling_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['price'],
                                            'discount' => null,
                                            'created_by' => $request->user()->id,
                                        ]);
                                    }

                                    /**
                                     * Addon inventory update
                                     */
                                    if ($package_inclusion_product['quantity_per_day'] > 0 && $guest['age'] > $infant_max_age) {
                                        \App\Models\Booking\Addon::create([
                                            'booking_reference_number' => $booking['reference_number'],
                                            'guest_reference_number' => $guest['reference_number'],
                                            'code' => $package_inclusion_product['code'],
                                            'date' => Carbon::parse($array_period_dates[$i])->setTimezone('Asia/Manila'),
                                            'status' => 'valid',
                                            'created_by' => $request->user()->id,
                                        ]);
                                    }
            
                                    /**
                                     * Create Passes when product has pass stub
                                     */
                                    foreach ($package_inclusion_product['productPass'] as $product_pass) {
            
                                        Pass::createProductPasses($product_pass['stub_id'], $booking->reference_number, $guest['reference_number'], $arrival_date, $departure_date, $per_guest_package_inclusion->id);
            
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

                                if ($is_walkin) {
                                    $selling_price_type = 'Walkin Rate';
                                    $price = ($item['walkin_price'] == null) ? $price : $item['walkin_price'];
                                }
            
                                // Increment invoice total cost (if not infant)
                                if ($guest['age'] > $infant_max_age) {
                                    $invoice_total_cost = $invoice_total_cost + $price;
                                }

                                $per_guest_package_save = $booking->inclusions()->create([
                                    'invoice_id' => $newlyCreatedInvoice->id,
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
                                    'selling_price' =>$guest['age'] <= $infant_max_age ? 0 : $price,
                                    'selling_price_type' => $selling_price_type,
                                    'discount' => null,
                                    'created_by' => $request->user()->id,
                                ]);

                                foreach ($item->packageInclusions as $package_inclusions) {
                                    $package_inclusion_product = Product::where('code', $package_inclusions['product']['code'])->with('productPass')->first();

                                    $addons_per_product = \App\Models\Booking\Addon::where('code', $package_inclusion_product['code'])
                                                    ->where('status', 'valid')
                                                    ->where('date', $arrival_date)
                                                    ->whereHas('booking', function ($q) {
                                                        $q->whereNotIn('status', ['cancelled']);
                                                    })
                                                    ->count();

                                    if ($guest['age'] > $infant_max_age) {
                                        if ($addons_per_product >= $package_inclusion_product['quantity_per_day'] && $package_inclusion_product['quantity_per_day'] > 0) {

                                            $connection->rollBack();
                                            return response()->json(['error' => $package_inclusion_product['code'].'_FULL', 'message' => $package_inclusion_product['name'].' is full.'], 400);

                                        }
                                    }
            
                                    if ($package_inclusions['product']['type'] == 'per_guest') {
                                        $per_guest_package_inclusion = $booking->inclusions()->create([
                                            'invoice_id' => $newlyCreatedInvoice->id,
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
                                            'walkin_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['walkin_price'],
                                            'selling_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['price'],
                                            'discount' => null,
                                            'created_by' => $request->user()->id,
                                        ]);
                                    } else if ($package_inclusions['product']['type'] == 'per_booking') {
                                        $per_guest_package_inclusion = $booking->inclusions()->create([
                                            'invoice_id' => $newlyCreatedInvoice->id,
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
                                            'walkin_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['walkin_price'],
                                            'selling_price' => $guest['age'] <= $infant_max_age ? 0 : $package_inclusions['product']['price'],
                                            'discount' => null,
                                            'created_by' => $request->user()->id,
                                        ]);
                                    }

                                    /**
                                     * Addon inventory update
                                     */
                                    if ($package_inclusion_product['quantity_per_day'] > 0 && $guest['age'] > $infant_max_age) {
                                        \App\Models\Booking\Addon::create([
                                            'booking_reference_number' => $booking['reference_number'],
                                            'guest_reference_number' => $guest['reference_number'],
                                            'code' => $package_inclusion_product['code'],
                                            'date' => $arrival_date,
                                            'status' => 'valid',
                                            'created_by' => $request->user()->id,
                                        ]);
                                    }
            
                                    /**
                                     * Create Passes when product has pass stub
                                     */
                                    foreach ($package_inclusion_product['productPass'] as $product_pass) {
            
                                        Pass::createProductPasses($product_pass['stub_id'], $booking->reference_number, $guest['reference_number'], $arrival_date, $departure_date, $per_guest_package_inclusion->id);
            
                                    }
                                }
                        }
    
                    }
                    
                }
            }
        }

        /**
         * Save
         */
        // Per booking
        if (isset($per_booking_inclusions_to_save)) {
            $booking->inclusions()->saveMany($per_booking_inclusions_to_save);
        }

        // if (isset($per_booking_package_to_save)) {
        //     $booking->inclusions()->saveMany($per_booking_package_to_save);
        // }

        // // Per guest
        // if (isset($per_guest_inclusions_to_save)) {
        //     $booking->inclusions()->saveMany($per_guest_inclusions_to_save);
        // }

        // if (isset($per_guest_package_to_save)) {
        //     $booking->inclusions()->saveMany($per_guest_package_to_save);
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

            $connection->commit();

            return $newlyCreatedInvoice->refresh();
        }

    }
}
