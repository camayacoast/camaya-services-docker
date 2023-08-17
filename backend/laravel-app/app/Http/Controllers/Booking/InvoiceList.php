<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Invoice;

class InvoiceList extends Controller
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
        return Invoice::where('booking_reference_number', $request->booking_reference_number)
                        ->with(['inclusions' => function ($query) {
                            $query->with('guestInclusion');
                            $query->with('packageInclusions.guestInclusion');
                        }])
                        ->with('payments')
                        ->get();
    }
}
