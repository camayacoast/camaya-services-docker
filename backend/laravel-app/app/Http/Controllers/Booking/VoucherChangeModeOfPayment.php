<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\GeneratedVoucher;

class VoucherChangeModeOfPayment extends Controller
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
        $voucher = GeneratedVoucher::find($request->id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found.'], 400);
        }

        /**
         * Change voucer status
         */

         $voucher->update([
             'mode_of_payment' => $request->mode_of_payment == 'none' ? null :  $request->mode_of_payment,
         ]);

         /**
          * Activity logging
          */

          return response()->json($voucher, 200);
    }
}
