<?php

namespace App\Http\Controllers\OneBITS\Admin\Reports;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OneBITS\SalesReportManifest;

use App\Models\OneBITS\Ticket;

use DB;

class SalesReport extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke($start_date, $end_date, $download = false)
    {
        $columns = [
            'tickets.group_reference_number',
            \DB::raw("CONCAT(tickets.trip_number, ' ', origin.code, '-', destination.code) AS trip_details"),
            \DB::raw('COUNT(*) as total_ticket_count'),
            \DB::raw('(SELECT CONCAT(first_name, " ", last_name) FROM passengers WHERE tickets.passenger_id = passengers.id ORDER BY passengers.id ASC LIMIT 1) as first_passenger_name'),
            'payment_provider',
            'payment_reference_number',
            \DB::raw('SUM(amount) - COALESCE(SUM(discount), 0) as total_booking_amount'),
            'discount_id',
            'remarks',
            'ticket_type',
            'schedules.start_time',
            'schedules.end_time',
            'schedules.trip_date',
            'contact_number',
            'email'
        ];
        $end_date_copy = Carbon::parse($end_date)->endOfDay();

        $bookings = Ticket::select($columns)
            ->join('schedules', 'schedules.trip_number', '=', 'tickets.trip_number')
            ->leftJoin('routes', 'schedules.route_id', '=', 'routes.id')
            ->leftJoin('locations as origin', 'routes.origin_id', '=', 'origin.id')
            ->leftJoin('locations as destination', 'routes.destination_id', '=', 'destination.id')
            ->join('passengers', 'passengers.id', '=', 'tickets.passenger_id')
            ->whereBetween('schedules.trip_date', [$start_date, $end_date_copy])
            ->where('payment_status', 'paid')
            ->groupBy(
                'tickets.group_reference_number',
                'tickets.passenger_id',
                'trip_details',
                'payment_provider',
                'payment_reference_number',
                'discount_id',
                'remarks',
                'ticket_type',
                'schedules.start_time',
                'schedules.end_time',
                'schedules.trip_date',
                'contact_number',
                'email',
            )
            ->get();

        if ($download) {
                return Excel::download(
                    new SalesReportManifest($start_date, $end_date, $bookings), 
                    'report.xlsx'
                );  
        }

        return response()->json([
            'status' => true,
            'data' => $bookings,
        ]);
    }
}
