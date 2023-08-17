<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OccupancyDashboard extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // Check if parameter is a date
        $start_of_month = Carbon::parse($request->month.' '.$request->year)->startOfMonth()->format('Y-m-d');
        $end_of_month = Carbon::parse($request->month.' '.$request->year)->endOfMonth()->format('Y-m-d');

        // $end_date_param = $is_date ? \Carbon\Carbon::parse($start_date)->addDays(30)->format('Y-m-d') : now()->addDays(30)->format('Y-m-d');

        $start_date = $start_of_month." 00:00:00";
        $end_date = $end_of_month." 23:59:59";

        /**
         * Create period based on date range
         */
        $period = \Carbon\CarbonPeriod::create($start_of_month, $end_of_month);

        $room_reservations = \App\Models\Hotel\RoomReservation::where(function ($query) use ($start_date, $end_date, $end_of_month) {
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
                    ->whereIn('room_reservations.status', ['confirmed', 'pending', 'blackout', 'checked_in', 'checked_out'])
                    // ->whereNotIn('status', ['blackout'])
                    // ->whereHas('booking', function ($q) {
                    //     $q->whereNotIn('status', ['cancelled']);
                    // })
                    ->with(['booking' => function ($q) {
                        $q->select('id','reference_number','bookings.status');
                        $q->with('tags:booking_id,name');
                    }])
                    ->with('room.property:id,code')
                    ->with('room.type:id,code')
                    // ->whereIn('property_id', [1])
                    ->select('booking_reference_number', 'room_id', 'start_datetime', 'end_datetime', 'category', 'status')
                    ->get();

        $data = collect(collect(collect($room_reservations)
                    ->map( function ($item, $key) {
                        $reservation_start = \Carbon\Carbon::parse($item['start_datetime'])->setTimezone('Asia/Manila')->format('Y-m-d');
                        $reservation_end = \Carbon\Carbon::parse($item['end_datetime'])->subDays(1)->setTimezone('Asia/Manila')->format('Y-m-d');
                        $reservation_period = \Carbon\CarbonPeriod::create($reservation_start, $reservation_end);
                        $occupancy_dates = collect($reservation_period)->map( function ($i) { return $i->format('Y-m-d'); })->toArray();
                        
                        return [
                            // 'booking_ref' => $item['booking_reference_number'],
                            // 'status' => $item['status'],
                            'category' => $item['category'],
                            'occupancy_dates' => $occupancy_dates,
                            'room_type' => $item['room']['type']['code'],
                            'property_code' => $item['room']['property']['code'],
                            'tags' => isset($item['booking']['tags']) ? collect($item['booking']['tags'])->pluck("name")->all() : []
                        ];
                    })
                    ->values()
                    ->all())
                    ->mapToGroups( function ($item, $key) {
                        return [$item['room_type'] => $item['occupancy_dates']];
                    })
                    ->toArray())
                    ->map( function ($item, $key) {
                        return collect($item)->collapse()->countBy()->all();
                    })->all();

        return $data;
    }
}
