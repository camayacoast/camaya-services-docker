<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Booking;

class BookingsWithInclusionReport extends Controller
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

        return Booking::whereHas('inclusions', function ($query) use ($request) {
            $query->whereIn('code', $request->packagesToSearch);
            $query->orWhereIn('code', $request->productsToSearch);
        })
        ->with('customer')
        ->with('invoices')
        ->get();
    }
}
