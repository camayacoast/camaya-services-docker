<?php

namespace App\Http\Controllers\Booking\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking\Booking;
use DB;
use App\Models\Transportation\Route as TranspoRoute;
use App\Models\Transportation\Location;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ArrivalForecastPerSegment extends Controller
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
        $bookings = Booking::where('start_datetime', '>=', $request->start_date)
                        ->where('start_datetime', '<=', $request->end_date)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->select(DB::raw("DATE_FORMAT(start_datetime, '%Y-%m-%d') as date"), 'id', 'reference_number', 'type', 'mode_of_transportation', 'adult_pax', 'kid_pax', 'infant_pax')
                        ->with('tags')
                        ->whereDoesntHave('tags', function ($q) {
                            $q->where('name', 'Ferry Only');
                        })
                        ->with(['guests' => function($q) {
                            $q->whereIn('status', ['arriving', 'on_premise', 'checked_in'])
                            ->whereNull('deleted_at');
                        }])
                        ->with(['camaya_transportation' => function($q) {
                            $q->whereIn('trips.status', ['boarded', 'checked_in']);
                            $q->join('schedules', 'trips.trip_number', '=', 'schedules.trip_number');
                            $q->select('trips.*','schedules.route_id');
                        }])
                        ->get();

        // return collect($bookings)->groupBy('mode_of_transportation');
        // exit;

        $period = CarbonPeriod::create($request->start_date, $request->end_date);

        // Real Estate tags
        $real_estate_tags = [
            "ESLCC - Sales Agent",
            "ESLCC - Sales Client",
            "ESLCC- Sales Agent",
            "SDMB - Sales Director Marketing Budget",
            "Thru Agent - Paying",
            "Walk-in - Sales Client",
            "Walk-in - Sales Agent",
            "GOLF - RE",
            "RE - Golf",
            "RE - Tripping"
        ];

        // Home Owner tags
        $homeowner_tags = [
            "ESLCC - AFV",
            "ESLCC - CSV",
            "ESLCC - HOA",
            "ESLCC HOA",
            "HOA",
            "HOA - (Paying-promo)",
            "HOA - Paying Promo",
            "HOA - AF Unit Owner",
            "HOA - Voucher",
            "HOA – Gate Access",
            "HOA ACCESS STUB",
            "HOA - Access Stub",
            "HOA CLIENT",
            "HOA - Client",
            "HOA MEMBER",
            "HOA - Member",
            "HOA - Walk-in",
            "HOA - Golf",
            "GOLF - HOA",
            "HOA - Sales Director Marketing Budget",
            "Property Owner (Non-Member)",
            "Property Owner (HOA Member)",
            "Property Owner (Dependents)",
            "Property Owner (Guests)",
        ];

        // Commercial tags
        $commercial_tags = [
            "Commercial",
            "Commercial-Promo",
            "Commercial - Promo",
            "Commercial - Promo (Luventure)",
            "Commercial - PROMO",
            "Commercial - Promo (Camaya Summer)",
            "Commercial - Promo (Save Now, Travel Later)",
            "Commercial - Promo 12.12",
            "Commercial - Promo (12.12)",
            "Commercial (Admin)",
            "Commercial - Admin",
            "Commercial - Corre",
            "Commercial (Website)",
            "Commercial - Website",
            "Commercial - Walk-in",
            "Commercial - Golf",
            "Corporate FIT",
            "Corporate Sales",
            "CVoucher",
            "DTT WALK-IN",
            "DTT Walk-in",
            "DTT - Walk-in",
            "Paying - Walk-in",
            "OTA - KLOOK",
            "OTA - Klook",
            "Walk-in - Commercial",
            "Walk-in - DTT",
            "Walk-in - Paying",
            "GOLF - Commercial",
        ];

        // Employees tags
        $employees_tags = [
            "1Bataan ITS - Employee",
            "DEV 1",
            "DEV 1 - Employee",
            "DEV1 - Employee",
            "ESLCC - Employee",
            "ESLCC - Employee/Guest",
            "ESLCC - Employee / Guest",
            "ESLCC - Events/Guests",
            "ESLCC - Event/Guest",
            "ESLCC - Employee",
            "ESTLC - Employee",
            "ESTVC - EMP",
            "ESTVC - Employee",
            "People Plus - Employee",
            "Orion Sky - Employee",
            "SLA - Employee",
            "DS18 - Employee",
            "DS18 - Events Guest",
        ];

        $others_tags = [
            "House Use",
            "DEV1 - Events/Guests",
            "DEV1 - Event/Guest",
            "DEV 1 - Event/Guest",
            "ESLCC - GC",
            "ESLCC - Guest",
            "ESLCC -GUEST",
            "ESLCC FOC",
            "ESLCC - FOC",
            "ESLCC GUEST",
            "ESLCC- EVENTS/ GUESTS",
            "ESLCC-EVENTS/ GUESTS",
            "ESLCC-Events/Guests",
            "ESLCC - Event/Guest",
            "ESLCC-GUEST",
            "ESTLC-Guest",
            "ESTLC - Guest",
            "ESTLC - Event/Guest",
            "ESTVC - GC",
            "ESTVC - Events/Guests",
            "ESTVC-GUEST",
            "ESTVC-Guest/Events",
            "ESTVC -Guest",
            "ESTVC - Guest",
            "Magic Leaf - Event/Guest",
            "SLA - Events/Guests",
            "SLA - Event/Guest",
            "TA-Rates",
            "TA - Rates",
            "VIP Guest",
            "Orion Sky",
            "Orion Sky - Guest",
            "Golf Member",
            "Camaya Golf Voucher",
        ];

        // Other tags

        $array = [];

        foreach ($period as $datePeriod) {

            $array[$datePeriod->isoFormat('YYYY-MM-DD')] = [
                'date' => $datePeriod->isoFormat('YYYY-MM-DD'),
                'DT' => [
                    'ferry' => [
                        'real_estate' => 0,
                        'homeowner' => 0,
                        'commercial' => 0,
                        'employees' => 0,
                        'others' => 0,
                        'total' => 0,
                    ],
                    'by_land' => [
                        'real_estate' => 0,
                        'homeowner' => 0,
                        'commercial' => 0,
                        'employees' => 0,
                        'others' => 0,
                        'total' => 0,
                    ],
                ],
                'ON' => [
                    'ferry' => [
                        'real_estate' => 0,
                        'homeowner' => 0,
                        'commercial' => 0,
                        'employees' => 0,
                        'others' => 0,
                        'total' => 0,
                    ],
                    'by_land' => [
                        'real_estate' => 0,
                        'homeowner' => 0,
                        'commercial' => 0,
                        'employees' => 0,
                        'others' => 0,
                        'total' => 0,
                    ],
                ],  
            ];

        }

        $real_estate_tag_matches = false;
        $employees_tag_matches = false;
        $commercial_tag_matches = false;
        $homeowners_tag_matches = false;
        $others_tag_matches = false;

        foreach ($bookings as $key => $booking) {

            $transpo = ($booking['mode_of_transportation'] == 'camaya_transportation' ? 'ferry' : 'by_land');

            // if (isset($booking['trip_data']) && count($booking['trip_data']) == 1) {
            //     if (!in_array('EST-CMY', $booking['trip_data']) && in_array('CMY-EST', $booking['trip_data'])) {
            //         // $transpo = 'by_land';
            //     }
            // }

            $no_ticket_guests = 0;
 
            if ($booking['mode_of_transportation'] != 'camaya_transportation') {
                // own_vehicle, undecided, van_rental, camaya_vehicle, company_vehicle
                $total = count($booking['guests']);
            } else {
                // Get routes for EST-CMY and EST-FTT
                // $origin_location = Location::where('code', 'EST')->first();
                $destination_location_ids = Location::whereIn('code', ['CMY', 'FTT'])->pluck('id');

                $route_ids = TranspoRoute::whereIn('destination_id', $destination_location_ids)->pluck('id');

                $total = count(collect($booking['camaya_transportation'])->whereIn('route_id', $route_ids)->all());

                $no_ticket_guests = count($booking['guests']) - $total;
            }

            $tags = collect($booking['tags'])->pluck('name')->all();

            if ($tags) {

                foreach ($tags as $tag) {
                    if (in_array($tag, $real_estate_tags)) {
                        $real_estate_tag_matches = true;
                    } else if (in_array($tag, $employees_tags)) {
                        $employees_tag_matches = true;
                    } else if (in_array($tag, $commercial_tags)) {
                        $commercial_tag_matches = true;
                    } else if (in_array($tag, $homeowner_tags)) {
                        $homeowners_tag_matches = true;
                    } else {
                        $others_tag_matches = true;
                    }
                }

                if ($real_estate_tag_matches) {
                    $array[$booking['date']][$booking['type']][$transpo]['real_estate'] = $array[$booking['date']][$booking['type']][$transpo]['real_estate'] + $total;
                    
                    if ($no_ticket_guests) $array[$booking['date']][$booking['type']]['by_land']['real_estate'] = $array[$booking['date']][$booking['type']]['by_land']['real_estate'] + $no_ticket_guests;
                } else if ($commercial_tag_matches) {
                    $array[$booking['date']][$booking['type']][$transpo]['commercial'] = $array[$booking['date']][$booking['type']][$transpo]['commercial'] + $total;
                    
                    if ($no_ticket_guests) $array[$booking['date']][$booking['type']]['by_land']['commercial'] = $array[$booking['date']][$booking['type']]['by_land']['commercial'] + $no_ticket_guests;
                } else if ($homeowners_tag_matches) {
                    $array[$booking['date']][$booking['type']][$transpo]['homeowner'] = $array[$booking['date']][$booking['type']][$transpo]['homeowner'] + $total;

                    if ($no_ticket_guests) $array[$booking['date']][$booking['type']]['by_land']['homeowner'] = $array[$booking['date']][$booking['type']]['by_land']['homeowner'] + $no_ticket_guests;
                } else if ($employees_tag_matches) {
                    $array[$booking['date']][$booking['type']][$transpo]['employees'] = $array[$booking['date']][$booking['type']][$transpo]['employees'] + $total;

                    if ($no_ticket_guests) $array[$booking['date']][$booking['type']]['by_land']['employees'] = $array[$booking['date']][$booking['type']]['by_land']['employees'] + $no_ticket_guests;
                } else {
                    $array[$booking['date']][$booking['type']][$transpo]['others'] = $array[$booking['date']][$booking['type']][$transpo]['others'] + $total;

                    if ($no_ticket_guests) $array[$booking['date']][$booking['type']]['by_land']['others'] = $array[$booking['date']][$booking['type']]['by_land']['others'] + $no_ticket_guests;
                }                        

            } else {
                $array[$booking['date']][$booking['type']][$transpo]['others'] = $array[$booking['date']][$booking['type']][$transpo]['others'] + $total;
                
                if ($no_ticket_guests) $array[$booking['date']][$booking['type']]['by_land']['others'] = $array[$booking['date']][$booking['type']]['by_land']['others'] + $no_ticket_guests;
            }

            $array[$booking['date']][$booking['type']][$transpo]['total'] = $array[$booking['date']][$booking['type']][$transpo]['total'] + $total;
            if ($no_ticket_guests) $array[$booking['date']][$booking['type']]['by_land']['total'] = $array[$booking['date']][$booking['type']]['by_land']['total'] + $no_ticket_guests;

            $real_estate_tag_matches = false;
            $employees_tag_matches = false;
            $commercial_tag_matches = false;
            $homeowners_tag_matches = false;
            $others_tag_matches = false;

        }

        // return collect($tagss)->unique()->values()->all();
        return $array;
    }
}
