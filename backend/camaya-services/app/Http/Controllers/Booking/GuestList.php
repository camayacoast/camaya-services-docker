<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;
use Carbon\Carbon;

use App\User;

class GuestList extends Controller
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

        $guests = Guest::whereHas('booking', function ($query) use ($request) {
                    $query->whereDate('start_datetime', '<=', $request->date)
                        ->whereDate('end_datetime', '>=', $request->date);
                })
                ->with('passes')
                ->with(['booking' => function ($q) {
                    $q->select('reference_number','type','status', 'customer_id');
                    $q->with('customer:id,object_id,first_name,last_name');
                }])
                ->get();

        return $guests;
    }
}
