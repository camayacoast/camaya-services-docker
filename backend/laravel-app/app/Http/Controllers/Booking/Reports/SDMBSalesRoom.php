<?php

namespace App\Http\Controllers\Booking\Reports;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Models\Booking\Product;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SDMBSalesRoom extends Controller
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

        $bookings = \App\Models\Booking\Booking::whereBetween('bookings.start_datetime', [$start_date, $end_date])
                                            ->where('bookings.status', 'confirmed')
                                            ->where('bookings.type', 'ON')
                                            ->select('reference_number', 'sales_director_id', 'agent_id', 'customer_id', 'start_datetime', 'end_datetime', 'remarks')
                                            ->with(['customer', 'room_reservations_no_filter.room_type.property', 'sales_director', 'agent'])
                                            ->with(['inclusions' => function ($q) {
                                                $q->select('booking_reference_number', 'id', 'code', 'type', 'quantity', 'price');
                                                $q->where('code','EXTRAPAX');
                                                $q->orWhere('type', 'room_reservation');
                                                $q->whereNull('deleted_at');
                                            }])
                                            ->whereHas('tags', function ($q) {
                                                $q->select('booking_id');
                                                $q->where('name','SDMB - Sales Director Marketing Budget');
                                            })
                                            ->withCount(['inclusions as inclusions_grand_total' => function ($q) {
                                                $q->whereNull('deleted_at');
                                                $q->where('type', 'room_reservation');
                                                $q->select(\DB::raw('sum(price)'));
                                            }])
                                            ->withCount(['inclusions as inclusions_grand_total_on_package' => function ($q) {
                                                $q->whereNull('deleted_at');
                                                $q->where('type', 'room_reservation');
                                                $q->whereNotNull('parent_id');
                                                $q->select(\DB::raw('sum(original_price)'));
                                            }])
                                            ->get();


        foreach ($bookings as $booking) {
            $booking['hotel'] = '';
            $booking['room_type'] = [];
            $booking['no_of_rooms'] = count($booking->room_reservations_no_filter);
            $booking['check_in'] = $booking->start_datetime;
            $booking['check_out'] = $booking->end_datetime;
            $booking['no_of_nights'] = Carbon::parse($booking->start_datetime)->diffInDays(Carbon::parse($booking->end_datetime));

            $booking_inclusions = collect($booking['inclusions']);

            $filtered = $booking_inclusions->filter(function ($item) {
                return $item['code'] === 'EXTRAPAX' && $item['parent_id'] === null;
            });
            
            $filtered_result = $filtered->all(); // FILTERED RESULT
            $extra_pax_total_fee = 0;

            foreach ($filtered_result as $item) {
                $extra_pax_total_fee += $item['quantity'] * $item['price'];
            }

            $extra_pax_total_count = collect($filtered_result)->sum('quantity');

            $booking['extra_pax'] = $extra_pax_total_count;
            $booking['extra_pax_fee'] = $extra_pax_total_fee;

            $booking['grand_total'] =  $booking['no_of_nights'] 
                                        * ($booking['inclusions_grand_total'] ?? 0) 
                                        + ($booking['inclusions_grand_total_on_package'] ?? 0) 
                                        + $extra_pax_total_fee;

            foreach($booking->room_reservations_no_filter as $room_reservation) {
                $booking['hotel'] = $room_reservation->room_type->property->code;
                $room_allocation = RoomAllocation::find($room_reservation->allocation_used);
            }
        };

        if ($download) {
            return Excel::download(
                new ReportExport('reports.booking.sdmb-sales-room', $bookings), 
                'report.xlsx'
            );  
        }

        return response()->json([
            'status' => true,
            'data' => $bookings,
        ]);
    }
}