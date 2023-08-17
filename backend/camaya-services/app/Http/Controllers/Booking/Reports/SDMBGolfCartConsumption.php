<?php

namespace App\Http\Controllers\Booking\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use App\Models\Booking\Booking;
use App\Models\Booking\Customer;

use DB;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class SDMBGolfCartConsumption extends Controller
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

        // Get all SDs
        $sales_directors = User::where('user_type', 'agent')->role('Sales Director')
                    ->get();
        

        $array = [];
        $period = CarbonPeriod::create($request->start_date, $request->end_date);

        $dates = [];

        foreach ($period as $date) {
            $dates[$date->format('Y-m-d')]['date'] = $date->format('Y-m-d');
            $dates[$date->format('Y-m-d')]['golf_cart_rates'] = 0;
        }

        // Set days

        // Get bookings with golf cart under SDMB
        foreach ($sales_directors as $sales_director) {

            $agents_bookings = Booking::whereIn('status', ['confirmed'])
                    ->where('start_datetime', '>=', $request->start_date)
                    ->where('start_datetime', '<=', $request->end_date)
                    ->where( function ($query) use ($sales_director) {
                        $query->where('sales_director_id', $sales_director['id']);
                    })
                    ->with('golf_cart_inclusions')
                    ->whereHas('tags', function ($q){
                        $q->where('name', 'SDMB - Sales Director Marketing Budget');
                    })
                    ->get()
                    ->toArray();

            $consumptions = $dates;
            
            foreach ($agents_bookings as $agent_booking) {

                $date = Carbon::parse($agent_booking['start_datetime'])->setTimezone('Asia/Manila')->format('Y-m-d');

                $consumptions[$date] = [
                    'date' => $date,
                    'golf_cart_rates' => $consumptions[$date]['golf_cart_rates'] + collect($agent_booking['golf_cart_inclusions'])->sum('original_price'),
                ];
            }
            

            $array[] = [
                'sales_director' => [
                    'id' => $sales_director['id'],
                    'first_name' => $sales_director['first_name'],
                    'last_name' => $sales_director['last_name'],
                ],
                'consumptions' => $consumptions,
                // 'golf_cart_rates' => collect((array_filter($golf_cart_inclusions->all())))->sum('price'),
            ];

        }

        return $array;
    }
}
