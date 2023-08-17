<?php

namespace App\Http\Controllers\Hotel\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ReportExport;

use Carbon\Carbon;

class DailyArrivalDownload extends Controller
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

        $properties = [];

        if ($request->sands == 'true') {
            $properties[] = 'SANDS';
        }

        if ($request->af == 'true') {
            $properties[] = 'AF';
        }

        $selected_date = Carbon::parse($request->route('date'))->format('Y-m-d');

        $room_reservations = \App\Models\Hotel\RoomReservation::with(['booking' => function ($q) use ($request) {
            $q->with(['inclusions' => function ($query) {
                $query->where('type', '=', 'room_reservation');
            }]);
            $q->with('customer');
            $q->with('tags');
        }])
        ->whereHas('booking', function ($q) use ($request) {
            $q->whereNotIn('status', ['draft', 'cancelled']);
            $q->where('start_datetime', '=', date('Y-m-d 00:00:00', strtotime($request->route('date'))));
        })
        ->whereHas('room_type.property', function ($q) use ($properties) {
            $q->whereIn('code', $properties);
        })
        ->with('room')
        ->with('room_type')
        ->whereNotIn('status', ['cancelled', 'blackout', 'voided', 'transferred']);

        $booking_refs = collect($room_reservations->get())->pluck('booking_reference_number')->all();

        /**
         * Stay over guests
         */
        $stayover_rooms = \App\Models\Hotel\RoomReservation::join('bookings', 'room_reservations.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($selected_date) {
                $q->whereIn('bookings.status', ['confirmed', 'pending']);
                $q->where('bookings.start_datetime', '=', $selected_date);
                $q->orWhere('bookings.start_datetime', '<=', $selected_date)
                    ->where('bookings.end_datetime', '>=', $selected_date)
                    ->where('bookings.end_datetime', '!=', $selected_date);
        })
        ->whereHas('room_type.property', function ($q) use ($properties) {
            $q->whereIn('code', $properties);
        })
        ->whereNotIn('booking_reference_number', $booking_refs)
        ->whereNotIn('room_reservations.status', ['cancelled', 'blackout', 'voided', 'checked_out', 'transferred'])
        ->groupBy('booking_reference_number')
        ->selectRaw('COUNT(*) as room_count, bookings.adult_pax, bookings.kid_pax, bookings.infant_pax')
        ->get();

        $stayover_room_total = collect($stayover_rooms)->sum('room_count');
        $stayover_pax_total = collect($stayover_rooms)->sum( function ($i) {
            return $i['adult_pax'] + $i['kid_pax'] + $i['infant_pax'];
        });

        /**
         * House Use
         */
        $houseuse_rooms = \App\Models\Hotel\RoomReservation::join('bookings', 'room_reservations.booking_reference_number','=','bookings.reference_number')
            ->where(function ($q) use ($selected_date) {
                $q->whereIn('bookings.status', ['confirmed', 'pending']);
                $q->where('bookings.start_datetime', '=', $selected_date);
                $q->orWhere('bookings.start_datetime', '<=', $selected_date)
                    ->where('bookings.end_datetime', '>=', $selected_date)
                    ->where('bookings.end_datetime', '!=', $selected_date);
        })
        ->whereHas('booking.tags', function ($q) {
            $q->where('name', 'House Use');
        })
        ->whereHas('room_type.property', function ($q) use ($properties) {
            $q->whereIn('code', $properties);
        })
        ->whereNotIn('room_reservations.status', ['cancelled', 'blackout', 'voided', 'checked_out', 'transferred'])
        ->groupBy('booking_reference_number')
        ->selectRaw('COUNT(*) as room_count, bookings.adult_pax, bookings.kid_pax, bookings.infant_pax')
        ->get();

        $houseuse_room_total = collect($houseuse_rooms)->sum('room_count');
        $houseuse_pax_total = collect($houseuse_rooms)->sum( function ($i) {
            return $i['adult_pax'] + $i['kid_pax'] + $i['infant_pax'];
        });
        
        return \Maatwebsite\Excel\Facades\Excel::download(new ReportExport('reports.hotel.daily-arrival', ['room_reservations' => $room_reservations->get(), 'stayover_room_total' => $stayover_room_total, 'stayover_pax_total' => $stayover_pax_total, 'houseuse_room_total' => $houseuse_room_total, 'houseuse_pax_total' => $houseuse_pax_total ]), 'report.xlsx');
    }
}
