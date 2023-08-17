<?php

namespace App\Http\Controllers\Transportation\Reports;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Models\Booking\Customer;
use App\Models\Main\Role;
use App\Models\Transportation\Schedule;
use App\Models\Transportation\Trip;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class FerrySeatsPerSD extends Controller
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
        $data = [];

        if (!$start_date || !$end_date) {
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }

        $schedules = Schedule::join('routes', 'schedules.route_id', '=', 'routes.id')
            ->join('locations as origin_location', 'routes.origin_id', '=', 'origin_location.id')
            ->join('locations as destination_location', 'routes.destination_id', '=', 'destination_location.id')
            ->join('transportations', 'schedules.transportation_id', '=', 'transportations.id')
            ->whereBetween('trip_date', [$start_date, $end_date])
            ->where('schedules.status', '=', 'active')
            ->with('seatSegments')
            ->with('seatAllocations.segments.allowed_roles')
            ->with('seatAllocations.segments.allowed_users')
            ->with('seatAllocations.segments.trips.booking.guests')
            ->has('seatAllocations.segments.trips.booking')
            // ->with('trips.booking.guests')
            ->with(['transportation' => function ($q) {
                $q->withCount('activeSeats');
            }])
            ->select(
                'schedules.*',
                'origin_location.code as origin',
                'destination_location.code as destination',
                'transportations.name as transportation',
            )
            // ->addSelect([
            //     'allocated_seat' => \App\Models\Transportation\SeatAllocation::whereColumn('schedule_id', 'schedules.id')->selectRaw('IFNULL(SUM(seat_allocations.quantity), 0) as allocated_seat'),
            //     // 'allocated_seat' => \App\Models\Transportation\SeatSegment::whereColumn('trip_number', 'schedules.trip_number')->selectRaw('IFNULL((SUM(allocated) - SUM(used)), 0) as allocated_seat'),
            //     'boarded' => \App\Models\Transportation\Trip::whereColumn('trip_number', 'schedules.trip_number')->where('status', 'boarded')->selectRaw('IFNULL((COUNT(status)), 0) as boarded'),
            //     'checked_in' => \App\Models\Transportation\Trip::whereColumn('trip_number', 'schedules.trip_number')->where('status', 'checked_in')->selectRaw('IFNULL((COUNT(status)), 0) as checked_in'),
            //     'pending' => \App\Models\Transportation\Trip::whereColumn('trip_number', 'schedules.trip_number')->where('status', 'pending')->selectRaw('IFNULL((COUNT(status)), 0) as pending')
            // ])
            // ->addSelect([
            //     'available_seat' => \App\Models\Transportation\Transportation::whereColumn('id', 'schedules.transportation_id')->with('activeSeats')->select('activeSeats as available_seat')
            // ])
            ->get();
        
        $sort_counter = 0;
        $sort_storage = [];
        $schedules->map(function ($schedule) use (&$data, &$sort_counter, &$sort_storage) {
            foreach($schedule->seatAllocations as $seat_allocation) {
                foreach($seat_allocation->segments as $segment) {
                    // $allowed_users = $segment->allowed_users;
                    $market_segmentation = $seat_allocation->name . ', ' . $segment->name;
                    $segment_name = $segment->name;
                    
                    if (array_key_exists($segment_name, $sort_storage)) {
                        $sort = $sort_storage[$segment_name];
                    } else {                                                
                        $sort = $sort_counter;
                        $sort_storage[$segment_name] = $sort_counter;
                        $sort_counter = $sort_counter + 1;
                    }

                    $count = 0; 
                    $allowed_users = collect($segment->allowed_users)->pluck('user_id')->all();                  
                    collect($segment->trips)->map(function ($trip) use (&$count, $segment, $allowed_users) {
                        if ($trip->status !== 'cancelled' 
                            && $segment->id === $trip->seat_segment_id 
                            && isset($trip->booking)
                            && (in_array($trip->booking->created_by, $allowed_users) || $trip->booking->created_by === 1)) {

                            foreach($trip->booking->guests as $guest) {
                                if ($trip->guest_reference_number === $guest->reference_number) {
                                    $count = $count + 1;
                                }
                            }
                        }
                    });                        
                    
                    if (count($allowed_users)) {
                        $data[] = [
                            'id' => count($data),
                            'sales_director' => $market_segmentation,
                            'date' => $schedule->trip_date,
                            'name_of_ferry' => $schedule->transportation,
                            'etd' => $schedule->start_time,
                            'eta' => $schedule->end_time,
                            'total_pax_booked' => $count,
                            'sort' => $sort,
                        ];
                    }                    
                }
            }
        
            return $schedule;
        });

        // Log::debug($schedules);

        $data_sorted = collect($data)->sortBy('sort', SORT_NATURAL)->values()->all();
        
        if ($download) {
            return Excel::download(
                new ReportExport('reports.transportation.ferry-seats-per-sd', $data_sorted),
                'report.xlsx'
            );
        }

        return response()->json([
            'status' => true,
            'data' => $data_sorted,
        ]);
    }
}
