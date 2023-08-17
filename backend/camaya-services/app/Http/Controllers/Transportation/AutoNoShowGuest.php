<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use App\Models\Booking\Guest;
use App\Models\Transportation\Schedule;
use App\Models\Transportation\Trip;
use App\Models\OneBITS\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoNoShowGuest extends Controller
{
    /**
     * Handle the incoming request.
     *     
     */
    public function __invoke()
    {
        $filter_date_yesterday = Carbon::now()->subDay()->toDateString();
        $filter_date_today = Carbon::now()->toDateString();
        $schedules = Schedule::join('routes', 'schedules.route_id', '=', 'routes.id')
            ->join('locations as origin_location', 'routes.origin_id', '=', 'origin_location.id')
            ->join('locations as destination_location', 'routes.destination_id', '=', 'destination_location.id')
            ->join('transportations', 'schedules.transportation_id', '=', 'transportations.id')
            ->where('trip_date', '>=', $filter_date_yesterday)
            ->where('trip_date', '<=', $filter_date_today)
            ->where('schedules.status', '=', 'active')
            ->orderBy('end_time', 'asc')
            // ->with('seatSegments')
            // ->with('seatAllocations.segments.allowed_roles')
            // ->with('seatAllocations.segments.allowed_users')
            // ->with('seatAllocations.segments.trips.booking.guests')
            ->with('seatAllocations.segments.trips')
            // ->with('trips.booking.guests')
            // ->with(['transportation' => function ($q) {
            //     $q->withCount('activeSeats');
            // }])
            ->select(
                'schedules.*',
                'origin_location.code as origin',
                'destination_location.code as destination',
                'transportations.name as transportation',
            )            
            ->get();
        
        $debug = [];
        $guests_ref_numbers_no_showed = [];
        $hours_difference = 3;
        $datetime_now = Carbon::now();
        $schedules->map(function ($schedule) use (&$guests_ref_numbers_no_showed, $datetime_now, $hours_difference, &$debug) {
            $schedule_start_datetime = Carbon::create($schedule->trip_date . ' ' . $schedule->start_time);
            $schedule_end_datetime = Carbon::create($schedule->trip_date . ' ' . $schedule->end_time);
            $difference_in_time = $schedule_start_datetime->diffInHours($datetime_now, false);
            
            // Log::debug($schedule_end_datetime);
            // Log::debug($difference_in_time);

            // $debug[] = [
            //     'schedule' => $schedule,
            //     'datetime_now' => $datetime_now->toDayDateTimeString(),
            //     'schedule_start_datetime' => $schedule_start_datetime->toDayDateTimeString(),
            //     'schedule_end_datetime' => $schedule_end_datetime->toDayDateTimeString(),
            //     'difference_in_time' => $difference_in_time,
            //     'will_run' => $difference_in_time >= $hours_difference,
            // ];

            if ($difference_in_time >= $hours_difference) {
                foreach($schedule->seatAllocations as $seat_allocation) {
                    foreach($seat_allocation->segments as $segment) {
                        
                        collect($segment->trips)->map(function ($trip) use (&$guests_ref_numbers_no_showed) {
                            if (in_array($trip->status, ['pending', 'checked_in'])) {    
                                    $guests_ref_numbers_no_showed[] = $trip->guest_reference_number;

                                    // Trip::where('id', $trip->id)
                                    //         ->update([
                                    //             'status' => 'no_show',
                                    //             'updated_at' => Carbon::now(),
                                    //         ]);
                                    // if ($trip->guest_reference_number) {
                                    //     Guest::where('reference_number', $trip->guest_reference_number)
                                    //             ->update([
                                    //                 'status' => 'no_show',
                                    //                 'updated_at' => Carbon::now(),
                                    //             ]);               
                                    // }
                                    
                                    // if ($trip->ticket_reference_number) {
                                    //     Ticket::where('reference_number', $trip->ticket_reference_number)->update([
                                    //         'status' => 'cancelled',
                                    //     ]);
                                    // }             
                            }
                        });
                        
                    }
                }
            }
        });

        // Logs cancelled bookings
        if ($guests_ref_numbers_no_showed) {
            Log::info('Auto no show guests: '. implode(', ', $guests_ref_numbers_no_showed));
        }
        
        return implode(', ', $guests_ref_numbers_no_showed);
        // return $debug;

    }
}
