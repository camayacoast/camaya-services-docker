<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VoucherPaymentCancel extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
       

        return redirect()->away(env('CAMAYA_BOOKING_PORTAL_URL').'/voucher-payment-cancelled?transaction_reference_number='.$request->transaction_reference_number);
        
        // return 'User is canceled the payment.';
        // return redirect()->away(env('CAMAYA_BOOKING_PORTAL_URL'));
    }
}
