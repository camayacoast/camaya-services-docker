<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ScheduleList extends Controller
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
        return \App\Models\Transportation\Schedule::join('routes', 'schedules.route_id', '=', 'routes.id')
                    ->join('locations as origin_location', 'routes.origin_id', '=', 'origin_location.id')
                    ->join('locations as destination_location', 'routes.destination_id', '=', 'destination_location.id')
                    ->join('transportations', 'schedules.transportation_id', '=', 'transportations.id')
                    ->where('trip_date', $request->date)
                    ->with('seatSegments')
                    ->with('seatAllocations.segments.allowed_roles')
                    ->with('seatAllocations.segments.allowed_users')
                    ->with(['transportation' => function ($q) {
                        $q->withCount('activeSeats');
                    }])
                    ->select(
                        'schedules.*',
                        'origin_location.code as origin',
                        'destination_location.code as destination',
                        'transportations.name as transportation',
                    )
                    ->addSelect([
                        'allocated_seat' => \App\Models\Transportation\SeatAllocation::whereColumn('schedule_id', 'schedules.id')->selectRaw('IFNULL(SUM(seat_allocations.quantity), 0) as allocated_seat'),
                        // 'allocated_seat' => \App\Models\Transportation\SeatSegment::whereColumn('trip_number', 'schedules.trip_number')->selectRaw('IFNULL((SUM(allocated) - SUM(used)), 0) as allocated_seat'),
                        
                        'boarded' => \App\Models\Transportation\Trip::whereColumn('trip_number', 'schedules.trip_number')->where('status','boarded')->selectRaw('IFNULL((COUNT(status)), 0) as boarded'),

                        'boarded_adult_kid' => \App\Models\Transportation\Trip::whereColumn('trip_number', 'schedules.trip_number')->join('guests', 'guests.reference_number', '=', 'trips.guest_reference_number')->where('trips.status','boarded')->whereIn('guests.type', ['adult', 'kid'])->selectRaw('IFNULL((COUNT(trips.status)), 0) as boarded_adult_kid'),
                        
                        'checked_in' => \App\Models\Transportation\Trip::whereColumn('trip_number', 'schedules.trip_number')->where('status','checked_in')->selectRaw('IFNULL((COUNT(status)), 0) as checked_in'),
                        'pending' => \App\Models\Transportation\Trip::whereColumn('trip_number', 'schedules.trip_number')->where('status','pending')->selectRaw('IFNULL((COUNT(status)), 0) as pending'),
                        
                        'checked_in_adult_kid' => \App\Models\Transportation\Trip::whereColumn('trip_number', 'schedules.trip_number')->join('guests', 'guests.reference_number', '=', 'trips.guest_reference_number')->where('trips.status','checked_in')->whereIn('guests.type', ['adult', 'kid'])->selectRaw('IFNULL((COUNT(trips.status)), 0) as checked_in_adult_kid'),
                    ])
                    // ->addSelect([
                    //     'available_seat' => \App\Models\Transportation\Transportation::whereColumn('id', 'schedules.transportation_id')->with('activeSeats')->select('activeSeats as available_seat')
                    // ])
                    ->get();
        
    }
}
