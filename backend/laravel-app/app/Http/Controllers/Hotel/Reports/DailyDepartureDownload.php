<?php

namespace App\Http\Controllers\Hotel\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ReportExport;

use Carbon\Carbon;

class DailyDepartureDownload extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

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
            $q->where('end_datetime', '=', date('Y-m-d 00:00:00', strtotime($request->route('date'))));
        })
        ->whereHas('room_type.property', function ($q) use ($properties) {
            $q->whereIn('code', $properties);
        })
        ->with('room')
        ->with('room_type')
        ->with('checked_out_by_details:id,first_name,last_name')
        ->whereNotIn('status', ['cancelled', 'blackout', 'voided', 'transferred']);

        $booking_refs = collect($room_reservations->get())->pluck('booking_reference_number')->all();

        return \Maatwebsite\Excel\Facades\Excel::download(new ReportExport('reports.hotel.daily-departure', ['room_reservations' => $room_reservations->get()]), 'report.xlsx');
    }
}
