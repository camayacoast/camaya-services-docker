<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\GeneratedVoucher;

use Carbon\Carbon;

class VoucherChangePaidAt extends Controller
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

        $updateToDate = $request->paid_at ? Carbon::parse($request->date)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null;

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found.'], 400);
        }

        /**
         * Change voucer status
         */

         $voucher->update([
             'paid_at' => $updateToDate,
         ]);

         /**
          * Activity logging
          */

          return response()->json($voucher, 200);
    }
}
