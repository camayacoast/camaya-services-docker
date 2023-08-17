<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\GeneratedVoucher;

class ChangeVoucherStatus extends Controller
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

        $voucher = GeneratedVoucher::find($request->id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found.'], 400);
        }

        /**
         * Change voucer status
         */

         $voucher->update([
             'voucher_status' => $request->status,
         ]);

         /**
          * Activity logging
          */

          return response()->json($voucher, 200);
    }
}
