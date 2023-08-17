<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\Booking\Customer;
use App\Models\Hotel\RoomAllocation;
use DB;

class RoomReservationCalendar extends Controller
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
        // Check if parameter is a date
        $start = Carbon::parse($request->start_date)->setTimezone('Asia/Manila')->format('Y-m-d');
        $end = Carbon::parse($request->end_date)->setTimezone('Asia/Manila')->format('Y-m-d');

        $start_date = $start." 00:00:00";
        $end_date = $end." 23:59:59";

        /**
         * Create period based on date range
         */
        $period = \Carbon\CarbonPeriod::create($start, $end);

        $blackouts = \App\Models\Hotel\RoomReservation::where(function ($query) use ($start_date, $end_date, $end) {
                                    $query->where(function ($query) use ($start_date, $end_date) {
                                        $query->where('room_reservations.start_datetime', '<=', $start_date)
                                            ->where('room_reservations.end_datetime', '>=', $start_date);
                                    })->orWhere(function ($query) use ($start_date, $end_date) {
                                        $query->where('room_reservations.start_datetime', '<=', $end_date)
                                            ->where('room_reservations.end_datetime', '>=', $end_date);
                                    })->orWhere(function ($query) use ($start_date, $end_date) {
                                        $query->where('room_reservations.start_datetime', '>=', $start_date)
                                            ->where('room_reservations.end_datetime', '<=', $end_date);
                                    });
                                })
                                ->whereIn('room_reservations.status', ['blackout'])
                                ->with('room.property:id,code')
                                ->with('room.type:id,code')
                                ->with('booked_by')
                                ->select(
                                        'booking_reference_number', 
                                        'room_id',
                                        DB::raw("DATE_FORMAT(room_reservations.start_datetime, '%Y-%m-%d') as start_datetime"),
                                        // DB::raw("DATE_FORMAT(room_reservations.start_datetime, '%H:%i') as check_in_time"),
                                        DB::raw("DATE_FORMAT(room_reservations.end_datetime, '%Y-%m-%d') as end_datetime"),
                                        // DB::raw("DATE_FORMAT(room_reservations.end_datetime, '%H:%i') as check_out_time"),
                                        'category',
                                        'room_reservations.status',

                                        'room_reservations.description',

                                        'room_reservations.created_by',
                                )
                                ->get();

        $room_reservations = \App\Models\Hotel\RoomReservation::where(function ($query) use ($start_date, $end_date, $end) {
                        $query->where(function ($query) use ($start_date, $end_date) {
                            $query->where('room_reservations.start_datetime', '<=', $start_date)
                                ->where('room_reservations.end_datetime', '>=', $start_date);
                        })->orWhere(function ($query) use ($start_date, $end_date) {
                            $query->where('room_reservations.start_datetime', '<=', $end_date)
                                ->where('room_reservations.end_datetime', '>=', $end_date);
                        })->orWhere(function ($query) use ($start_date, $end_date) {
                            $query->where('room_reservations.start_datetime', '>=', $start_date)
                                ->where('room_reservations.end_datetime', '<=', $end_date);
                        });
                    })
                    ->join('bookings', function ($join) {
                        $join->on('room_reservations.booking_reference_number', '=', 'bookings.reference_number')
                             ->whereIn('bookings.status', ['confirmed', 'pending']);
                    })
                    ->whereIn('room_reservations.status', ['confirmed', 'pending', 'blackout', 'checked_in', 'checked_out'])
                    ->with('room.property:id,code')
                    ->with('room.type:id,code')
                    ->with(['booking' => function ($q) {
                        $q->with('tags:booking_id,name');
                    }])
                    // ->whereIn('property_id', [1])
                    ->select(
                            'booking_reference_number', 
                            'room_id',
                            DB::raw("DATE_FORMAT(room_reservations.start_datetime, '%Y-%m-%d') as start_datetime"),
                            // DB::raw("DATE_FORMAT(room_reservations.start_datetime, '%H:%i') as check_in_time"),
                            DB::raw("DATE_FORMAT(room_reservations.end_datetime, '%Y-%m-%d') as end_datetime"),
                            // DB::raw("DATE_FORMAT(room_reservations.end_datetime, '%H:%i') as check_out_time"),
                            'category',
                            'room_reservations.status',
                            'room_reservations.allocation_used',
                            
                            'bookings.status as booking_status',
                            'bookings.adult_pax as adult_pax',
                            'bookings.kid_pax as kid_pax',
                            'bookings.infant_pax as infant_pax',
                            'room_reservations.created_by',
                    )
                    ->addSelect([
                        'customer_first_name' => Customer::whereColumn('id', 'bookings.customer_id')
                                            // ->select('first_name', 'last_name')
                                            ->select('first_name')
                                            ->limit(1)
                        ,
                        'customer_last_name' => Customer::whereColumn('id', 'bookings.customer_id')
                                            // ->select('first_name', 'last_name')
                                            ->select('last_name')
                                            ->limit(1)
                    ])
                    ->get();

        $allocations_used = collect(collect($room_reservations)->pluck('allocation_used')->all())->flatten()->unique()->values()->all();

        $allocations = RoomAllocation::whereIn('id', $allocations_used)->select('id', 'entity')->get();

        $data = collect(
                    collect(
                        collect($room_reservations)->merge($blackouts)->all()
                    )
                    ->map( function ($item, $key) use ($allocations) {
                        $reservation_start = \Carbon\Carbon::parse($item['start_datetime'])->setTimezone('Asia/Manila')->format('Y-m-d');
                        $reservation_end = \Carbon\Carbon::parse($item['end_datetime'])->subDays(1)->setTimezone('Asia/Manila')->format('Y-m-d');
                        $reservation_period = \Carbon\CarbonPeriod::create($reservation_start, $reservation_end);
                        $occupancy_dates = collect($reservation_period)->map( function ($i) { return $i->format('Y-m-d'); })->toArray();
                        
                        return [
                            'booking_ref' => $item['booking_reference_number'],
                            'booking_status' => $item['booking']['status'] ?? 'blocking',

                            'customer_first_name' => $item['customer_first_name'] ?? '',
                            'customer_last_name' => $item['customer_last_name'] ?? '',

                            'market_segmentation' => collect($item['allocation_used'])->map( function ($i) use ($allocations) {
                                return collect($allocations)->firstWhere('id', $i)['entity'];
                            })->values()->all(),

                            'status' => $item['status'],
                            'description' => $item['description'],
                            'category' => $item['category'],
                            'occupancy_dates' => $occupancy_dates,
                            'room_type' => $item['room']['type']['code'],
                            'room_number' => $item['room']['number'],
                            'property_code' => $item['room']['property']['code'],
                            'tags' => isset($item['booking']['tags']) ? collect($item['booking']['tags'])->pluck("name")->all() : [],

                            'blocked_by' => $item['status'] == 'blackout' ? $item['booked_by']['first_name']." ".$item['booked_by']['last_name'] : null
                        ];
                    })
                    ->values()
                    ->all())
                    // ->mapToGroups( function ($item, $key) {
                    //     return [$item['room_type'] => $item['occupancy_dates']];
                    // })
                    ->toArray();
                    // ->map( function ($item, $key) {
                    //     return collect($item)->collapse()->countBy()->all();
                    // })->all();

        return $data;
    }
}
