<?php

namespace App\Http\Controllers\Booking\Reports;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class CommercialSales extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke($start_date, $end_date, $download = false)
    {
        //
        // if (env('APP_ENV') === 'production') {
        //     return false;
        // }

        $data = Booking::query()
                    ->where('status', '=', 'confirmed')
                    ->whereBetween('start_datetime', [$start_date, $end_date])
                    ->orderBy('start_datetime', 'asc')
                    ->with(['customer', 'guests', 'inclusions', 'invoices', 'room_reservations_no_filter', 'tags'])
                    ->get();
        
        $data->map(function ($data) {
            $data->date = Carbon::parse($data->start_datetime)->toFormattedDateString();
            $data->date_created = Carbon::parse($data->created_at)->toFormattedDateString();
            $data->market_segmentation = '';
            $data->amount = collect($data->invoices)->pluck('grand_total')->sum();
            $data->hotel = '';
            $data->room_type = '';
            $data->no_of_rooms = $data->type == 'ON' ? count($data->room_reservations_no_filter) : '';
            $data->check_in = $data->type == 'ON' ? Carbon::parse($data->start_datetime)->toFormattedDateString() : '';
            $data->check_out = $data->type == 'ON' ? Carbon::parse($data->end_datetime)->toFormattedDateString() : '';
            $data->no_of_nights = $data->type == 'ON' 
                ? Carbon::parse($data->start_datetime)->diffInDays(Carbon::parse($data->end_datetime))
                : '';

            foreach($data->room_reservations_no_filter as $room_reservation) {
                $room_type = RoomType::find($room_reservation->room_type_id)->with('property')->first();
                $data->hotel = $room_type->property->code;
                $data->room_type = $room_type->name;
                $room_allocation = RoomAllocation::find($room_reservation->allocation_used);
                $data->market_segmentation = $room_allocation->implode('entity', ', ');
            }

            return $data;
        });

        if ($download) {
            return Excel::download(
                new ReportExport('reports.booking.commercial-sales', $data), 
                'report.xlsx'
            );  
        }

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }
}
