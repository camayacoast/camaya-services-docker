<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Schedule;
use App\Models\Transportation\SeatAllocation;
use App\Models\Transportation\SeatSegment;
use App\Models\Transportation\SeatSegmentAllow;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

use App\Http\Requests\Transportation\GenerateScheduleRequest;

class GenerateSchedules extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(GenerateScheduleRequest $request)
    {
        //
        // return $request->all();

        // route_id
        // transportation_id
        // trip_number
        // status
        // trip_date
        // start_time
        // end_time
        // created_by
    
        $connection = \DB::connection('camaya_booking_db');
        $connection->beginTransaction();

        $start_date = Carbon::parse($request->date_range[0])->setTimezone('Asia/Manila')->format('Y-m-d');
        $end_date = Carbon::parse($request->date_range[1])->setTimezone('Asia/Manila')->format('Y-m-d');

        $departure_time = Carbon::parse($request->departure_time)->setTimezone('Asia/Manila')->format('H:i:s');
        $arrival_time = Carbon::parse($request->arrival_time)->setTimezone('Asia/Manila')->format('H:i:s');

        $period = CarbonPeriod::create($request->date_range[0], $request->date_range[1]);

        if ($request->no_schedule_dates) {
            $period = $request->no_schedule_dates;
        }        

        /**
         * Check if schedule already exists
         * transportation_id, route_id, trip_date, start_time
         */
        $schedulesExists = [];
        $no_schedule_dates = [];
        foreach ($period as $date) {
            $formattedDate = $request->no_schedule_dates ? $date : $date->setTimezone('Asia/Manila')->format('Y-m-d');

            $sched = Schedule::where('transportation_id', $request->transportation_id)
                            ->where('route_id', $request->route_id)
                            ->where('trip_date', $formattedDate)
                            ->where('start_time', $departure_time)
                            ->first();

            if ($sched) {
                $schedulesExists[] = $sched;
            }

            if (!$sched) {
                $no_schedule_dates[] = $formattedDate;
            }
        }

        if ($schedulesExists) {
            $connection->rollBack();

            return response()->json([
                'error' => 'SCHEDULES_ALREADY_EXISTS',
                'message' => 'Schedules already exists ['.implode(", " ,collect($schedulesExists)->pluck('trip_date')->all()).'].',
                'data' => $schedulesExists,
                'no_schedule_dates' => $no_schedule_dates,
            ], 400);
        }

        // $dates = [];
        $createdSchedules = [];

        foreach ($period as $date) {
            $formattedDate = $request->no_schedule_dates ? $date : $date->setTimezone('Asia/Manila')->format('Y-m-d');
            $trip_number = Schedule::generateTripNumber();

            // $dates[] = $formattedDate;

            $newSchedule = Schedule::create([
                'route_id' => $request->route_id,
                'transportation_id' => $request->transportation_id,
                'trip_number' => $trip_number,
                'status' => 'for_review', // for_review, active, delayed, cancelled
                'trip_date' => $formattedDate,
                'start_time' => $departure_time,
                'end_time' => $arrival_time,
                'created_by' => $request->user()->id,
            ]);

            // Create the seat allocations
            foreach ($request->seat_allocations as $seat_allocation) {
                $newSeatAllocation = SeatAllocation::create([
                    'schedule_id' => $newSchedule->id, 
                    'name' => $seat_allocation['name'],
                    // 'category' => $seat_allocation['category'],
                    'quantity' => $seat_allocation['quantity'],
                    'allowed_roles' => $seat_allocation['allowed_roles'] ?? [],
                ]);

                // Create the seat segments
                // $seat_segments_to_save = [];

                if (isset($seat_allocation['seat_segments'])) {
                    foreach ($seat_allocation['seat_segments'] as $seat_segment) {
                        $newSeatSegment = SeatSegment::create([
                            'trip_number' => $trip_number,
                            'seat_allocation_id' => $newSeatAllocation->id,
                            'name' => $seat_segment['name'],
                            'allocated' => $seat_segment['allocated'],
                            'rate' => $seat_segment['rate'],
                            'active' => 0,
                            'booking_type' => $seat_segment['booking_type'],
                            'status' => $seat_segment['status'],
                            'trip_link' => $seat_segment['trip_link'] ?? null,
                            // 'allowed_roles' => json_encode($seat_segment['allowed_roles'] ?? []),
                            // 'allowed_users' => json_encode($seat_segment['allowed_users'] ?? []),
                            'updated_by' => $request->user()->id,
                        ]);

                        if (isset($seat_segment['allowed_roles'])) {
                            foreach ($seat_segment['allowed_roles'] as $seat_segment_role) {
                                SeatSegmentAllow::create([
                                    'seat_segment_id' => $newSeatSegment->id,
                                    'role_id' => $seat_segment_role,
                                ]);
                            }
                        }

                        if (isset($seat_segment['allowed_users'])) {
                            foreach ($seat_segment['allowed_users'] as $seat_segment_user) {
                                SeatSegmentAllow::create([
                                    'seat_segment_id' => $newSeatSegment->id,
                                    'user_id' => $seat_segment_user,
                                ]);
                            }
                        }
                    }
                }

                // $newSchedule->seatSegments()->createMany($seat_segments_to_save);
            }

            $createdSchedules[] = $newSchedule;
        }

        $connection->commit();

        return [
            $createdSchedules,
            $trip_number,
        ];
    }
}
