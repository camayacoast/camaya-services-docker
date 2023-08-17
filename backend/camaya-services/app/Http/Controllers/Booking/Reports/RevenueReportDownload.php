<?php

namespace App\Http\Controllers\Booking\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Exports\ReportExport;
use Maatwebsite\Excel\Facades\Excel;

class RevenueReportDownload extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {      

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

        $bpo_tags = [
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

            // hoa
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

            // employee
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

            // others
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

        $start_date = \Carbon\Carbon::parse($request->start_date)->setTimezone('Asia/Manila')->format('Y-m-d');
        $end_date = \Carbon\Carbon::parse($request->end_date)->setTimezone('Asia/Manila')->format('Y-m-d');

        $inclusions = \App\Models\Booking\Inclusion::join('bookings', 'bookings.reference_number', '=', 'inclusions.booking_reference_number')
                                                    ->select(
                                                        'inclusions.booking_reference_number',
                                                        'inclusions.item',
                                                        'inclusions.quantity',
                                                        'inclusions.selling_price',
                                                        'inclusions.walkin_price',
                                                        \DB::raw("DATE_FORMAT(bookings.start_datetime, '%W') as day"),
                                                        'bookings.adult_pax',
                                                        'bookings.kid_pax'
                                                    )
                                                    ->with(['booking' => function ($q) {
                                                        $q->select('reference_number', 'id');
                                                        $q->with('tags');
                                                    }])
                                                    ->where('bookings.start_datetime', '>=', $start_date)
                                                    ->where('bookings.start_datetime', '<=', $end_date)
                                                    ->where('bookings.status', 'confirmed')
                                                    ->whereIn('inclusions.type', ['package'])
                                                    // ->take(100)
                                                    ->get();

        $promos = collect($inclusions)->pluck('item')->unique()->values()->all();

        $data = [];

        foreach ($promos as $item) {
            $data[] = [
                'promo' => $item,
                'brn' => [],
                'bpo' => [
                    'wd' => [
                        'pax' => 0,
                        'sales' => 0,
                    ],
                    'we' => [
                        'pax' => 0,
                        'sales' => 0,
                    ],
                ],
                're' => [
                    'wd' => [
                        'pax' => 0,
                        'sales' => 0,
                    ],
                    'we' => [
                        'pax' => 0,
                        'sales' => 0,
                    ],
                ],
            ];
        }

        foreach ($inclusions as $inclusion) {

            $index = collect($data)->search( function($item) use ($inclusion) {
                    return $inclusion['item'] == $item['promo'];
            });

            $real_estate_tag_matches = false;
            $bpo_tag_matches = false;

        $tags = collect($inclusion['booking']['tags']->pluck('name')->all());

            if ($tags) {
                foreach ($tags as $tag) {
                    if (in_array($tag, $real_estate_tags)) {
                        $real_estate_tag_matches = true;
                    } else if (in_array($tag, $bpo_tags)) {
                        $bpo_tag_matches = true;
                    }   
                }

                if ($real_estate_tag_matches) {
                    
                    if (in_array($inclusion['day'], ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])) {
                        $data[$index]['re']['wd']['sales'] = $data[$index]['re']['wd']['sales'] + ($inclusion['selling_price'] * $inclusion['quantity']);

                        //pax
                        if (! in_array($inclusion['booking_reference_number'], $data[$index]['brn'])) {
                            $data[$index]['re']['wd']['pax'] = $data[$index]['re']['wd']['pax'] + ($inclusion['adult_pax'] + $inclusion['kid_pax']);
                            $data[$index]['brn'][] = $inclusion['booking_reference_number'];
                        }
                    } else {
                        $data[$index]['re']['we']['sales'] = $data[$index]['re']['we']['sales'] + ($inclusion['selling_price'] * $inclusion['quantity']);

                        //pax
                        if (! in_array($inclusion['booking_reference_number'], $data[$index]['brn'])) {
                            $data[$index]['re']['we']['pax'] = $data[$index]['re']['we']['pax'] + ($inclusion['adult_pax'] + $inclusion['kid_pax']);
                            $data[$index]['brn'][] = $inclusion['booking_reference_number'];
                        }
                    }
                    
                } else if ($bpo_tag_matches) {
                    if (in_array($inclusion['day'], ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])) {
                        $data[$index]['bpo']['wd']['sales'] = $data[$index]['bpo']['wd']['sales'] + ($inclusion['selling_price'] * $inclusion['quantity']);

                        //pax
                        if (! in_array($inclusion['booking_reference_number'], $data[$index]['brn'])) {
                            $data[$index]['bpo']['wd']['pax'] = $data[$index]['bpo']['wd']['pax'] + ($inclusion['adult_pax'] + $inclusion['kid_pax']);
                            $data[$index]['brn'][] = $inclusion['booking_reference_number'];
                        }
                    } else {
                        $data[$index]['bpo']['we']['sales'] = $data[$index]['bpo']['we']['sales'] + ($inclusion['selling_price'] * $inclusion['quantity']);

                        //pax
                        if (! in_array($inclusion['booking_reference_number'], $data[$index]['brn'])) {
                            $data[$index]['bpo']['we']['pax'] = $data[$index]['bpo']['we']['pax'] + ($inclusion['adult_pax'] + $inclusion['kid_pax']);
                            $data[$index]['brn'][] = $inclusion['booking_reference_number'];
                        }
                    }
                }
            }
        }

        $data2 = [
            'start_date' => $start_date, 
            'end_date' => $end_date,
            'data' => $data
        ];

        $response = Excel::download(
            new ReportExport('reports.booking.revenue-report', $data2),
            'Revenue-Report.xlsx',
        );

        // ob_end_clean();

        return $response;

    }
}
