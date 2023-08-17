<?php

namespace App\Http\Controllers\Booking\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use App\Models\Booking\Booking;
use App\Models\Booking\Customer;
use App\Models\Booking\Inclusion;

use DB;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class SDMBGolfPlayConsumption extends Controller
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

        // Get bookings with golf cart under SDMB
        foreach ($sales_directors as $sales_director) {

            $consumptions = [];

            $inclusions = Inclusion::where('code', 'like', '%GOLFPLAY%')
                            ->whereIn('type', ['package'])
                            ->with('booking')
                            ->whereHas('booking', function ($q) use ($request, $sales_director) {
                                $q->whereIn('status', ['confirmed'])
                                ->whereHas('tags', function ($q) {
                                    $q->where('name', 'SDMB - Sales Director Marketing Budget');
                                })         
                                ->where('start_datetime', '>=', $request->start_date)
                                ->where('start_datetime', '<=', $request->end_date)
                                ->where( function ($query) use ($sales_director) {
                                    $query->where('sales_director_id', $sales_director['id']);
                                });
                            })->get();

            foreach ($period as $date) {

                $filtered = collect($inclusions)
                            ->filter( function ($item) use ($date) {
                                    return Carbon::parse(strtotime($item['booking']['start_datetime']))->setTimezone('Asia/Manila')->format('Y-m-d') === $date->format('Y-m-d');
                            })->all();

                $consumptions[$date->format('Y-m-d')]['date'] = $date->format('Y-m-d');
                $consumptions[$date->format('Y-m-d')]['golf_play_rates'] = collect($filtered)->sum('price');
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
