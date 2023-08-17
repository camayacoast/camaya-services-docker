<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\AmortizationPenalty;

class UpdatePenaltyDefaultDiscount extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $reservation_number = $request->reservation_number;
        $discount = $request->discount;

        Reservation::where('reservation_number', $reservation_number)->update([
            'default_penalty_discount_percentage' => $discount
        ]);

        // AmortizationPenalty::where('reservation_number', $reservation_number)
        //     ->whereNull('paid_at')->update([
        //         'discount' => $discount
        //     ]);

    }
}
