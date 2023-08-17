<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Voucher;
use App\Models\Booking\GeneratedVoucher;

use Carbon\Carbon;

class GenerateNewVoucher extends Controller
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

        $voucher = Voucher::find($request->voucher_stub);

        // return $voucher;

        if (!$voucher) {
            return response()->json(['message' => 'Voucher stub not found.'], 400);
        }

        
        /**
         * Generate new voucher
         */
        /**
         * Generate New Unique Voucher Code
         */ 

         $prefix = "SVD";

        if ($voucher->availability == 'for_overnight') {
            $prefix = "SVO";
        }

        $voucher_code = $prefix."-".\Str::upper(\Str::random(6));

        // Creates a new reference number if it encounters duplicate
        while (GeneratedVoucher::where('voucher_code', $voucher_code)->exists()) {
            $voucher_code = $prefix."-".\Str::upper(\Str::random(6));
        }

        $transaction_reference_number = "VT-".\Str::upper(\Str::random(6));

        // Creates a new reference number if it encounters duplicate
        while (GeneratedVoucher::where('transaction_reference_number', $transaction_reference_number)->exists()) {
            $transaction_reference_number = "VT-".\Str::upper(\Str::random(6));
        }

        // return $voucher_code;


        $newVoucher = GeneratedVoucher::create([
            'transaction_reference_number' => $transaction_reference_number,
            'customer_id' => $request->customer,
            'voucher_id' => $voucher->id,
            'voucher_code' => $voucher_code,
            'type' => $voucher->type,
            'description' => $voucher->description,
            'availability' => $voucher->availability,
            'category' => $voucher->category,
            'mode_of_transportation' => $voucher->mode_of_transportation,
            'allowed_days' => $voucher->allowed_days,
            'exclude_days' => $voucher->exclude_days,
            'validity_start_date' => Carbon::parse(strtotime($voucher->booking_start_date))->setTimezone('Asia/Manila'),
            'validity_end_date' => Carbon::parse(strtotime($voucher->booking_end_date))->setTimezone('Asia/Manila'),

            'price' => $voucher->price,

            'voucher_status' => 'new',
            'used_at' => null,

            'payment_status' => 'unpaid',
            'paid_at' => null,

            'created_by' => $request->user()->id,
        ]);


        if (!$newVoucher) {
            return response()->json(['message' => 'No voucher was generated.'], 400);
        }

        $newVoucher->load('voucher');

        return response()->json($newVoucher, 200);


    }
}
