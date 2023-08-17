<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\GeneratedVoucher;

class GeneratedVouchersList extends Controller
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

        return GeneratedVoucher::with('voucher.images')
                    ->with('customer')
                    ->with('created_by')
                    ->orderBy('created_at', 'desc')
                    ->get();
    }
}
