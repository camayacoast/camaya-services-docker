<?php

namespace App\Http\Controllers\Booking\Reports;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Models\Booking\Customer;
use App\Models\RealEstate\SalesTeam;
use App\Models\RealEstate\SalesTeamMember;
use App\Models\Transportation\Schedule;
use App\Models\Transportation\Trip;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class DailyBookingPerSD extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke($start_date, $end_date, $download = false)
    {      

        $data = [];

        if (!$start_date || !$end_date) {
            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        }

        $sales_teams = SalesTeam::with(['owner.user'])->get();
        $sales_team_members = [];
        foreach($sales_teams as $k=>$sales_team) {
            $members = SalesTeamMember::query()
                ->where('team_id', '=', $sales_team->id)
                ->with(['user'])
                ->get();
            
            $sales_team_members[$sales_team->id] = $members;

            $member_ids = [];
            $member_emails = [];
            $member_customer_ids = [];
            $members->map(function ($item, $key) use (&$member_ids, &$member_emails, &$member_customer_ids) {
                $member_ids[] = $item->user->id;
                $member_emails[] = $item->user->email;

                $customer = Customer::query()
                                ->where('object_id', '=', $item->user->object_id)
                                ->orWhere('email', '=', $item->user->email)
                                ->first();

                if ($customer) {
                    $member_customer_ids[] = $customer->id;
                }

                return $item;
            });

            $data[$sales_team->id] = [
                'id' => $sales_team->id,
                'sd_name' => $sales_team->owner->user->first_name . ' ' . $sales_team->owner->user->last_name,
                'counter_ferry_arriving' => 0,
                'counter_ferry_arrived' => 0,
                'counter_ferry_cancelled' => 0,
                'counter_ferry_no_show' => 0,
                'counter_ferry_total_bookings' => 0,
                'counter_land_arriving' => 0,
                'counter_land_arrived' => 0,
                'counter_land_cancelled' => 0,
                'counter_land_no_show' => 0,
                'counter_land_total_bookings' => 0,
                'members' => json_encode($members),
                'members_ids' => $member_ids,
                'members_emails' => $member_emails,
                'member_customer_ids' => $member_customer_ids,                
            ];            
        }        

        $schedules = Schedule::join('routes', 'schedules.route_id', '=', 'routes.id')
            ->join('locations as origin_location', 'routes.origin_id', '=', 'origin_location.id')
            ->join('locations as destination_location', 'routes.destination_id', '=', 'destination_location.id')
            ->join('transportations', 'schedules.transportation_id', '=', 'transportations.id')
            ->whereBetween('trip_date', [$start_date, $end_date])
            ->where('schedules.status', '=', 'active')
            ->where('origin_location.code', '=', 'EST')
            // ->where('destination_location.code', '=', 'CMY')
            // ->with('seatSegments')
            ->with('seatAllocations.segments.allowed_roles')
            ->with('seatAllocations.segments.allowed_users')
            ->with('seatAllocations.segments.trips.booking.guests')
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
        
        $schedules->map(function ($schedule) use (&$data) {

            foreach($schedule->seatAllocations as $seat_allocation) {
                foreach($seat_allocation->segments as $segment) {                    

                    //ferry bookings
                    $trips = Trip::query()
                            ->where('trip_number', '=', $segment->trip_number)
                            ->where(function($query) use ($segment) {
                                $query->where('seat_segment_id', '=', $segment->id);
                                $query->orWhere('status', '=', 'no_show');
                            })

                            ->with(['booking'])
                            ->get();

                    /*
                        Status
                        { text: 'Confirmed booking', value: 'arriving' },
                        { text: 'Pending', value: 'pending' },
                        { text: 'Checked-in', value: 'checked_in' },
                        { text: 'Boarded', value: 'boarded' },
                        { text: 'No show', value: 'no_show' },
                        { text: 'Cancelled', value: 'cancelled' },
                    */
                    
                    foreach ($data as $sales_id=>$data_value) {
                        foreach($trips as $trip) {
                            if ((in_array($trip->booking->created_by, $data_value['members_ids'])
                                || in_array($trip->booking->customer_id, $data_value['member_customer_ids']))) {
                                switch ($trip->status) {
                                    case 'cancelled':
                                        $data[$sales_id]['counter_ferry_cancelled']++;
                                        $data[$sales_id]['counter_ferry_total_bookings']++;
                                        break;

                                    case 'no_show':
                                        $data[$sales_id]['counter_ferry_no_show']++;
                                        break;

                                    case 'arriving':
                                    case 'pending':
                                    case 'checked_in':
                                        $data[$sales_id]['counter_ferry_arriving']++;
                                        $data[$sales_id]['counter_ferry_total_bookings']++;
                                        break;

                                    default:
                                        $data[$sales_id]['counter_ferry_arrived']++;
                                        $data[$sales_id]['counter_ferry_total_bookings']++;
                                        break;
                                }
                            }
                        }   
                    }                     
                    
                }
            }

            return $schedule;
        });        
        
        //land bookings
        foreach($data as  $sales_id=>$data_value) {

            $land_bookings_tripping_coordinators = Booking::query()
                ->where('status', '=', 'confirmed')                
                ->where('mode_of_transportation', '!=', 'camaya_transportation')
                ->whereBetween('start_datetime', [$start_date, $end_date])
                ->where(function($query) use ($data_value) {
                    $query->whereIn('created_by', $data_value['members_ids']);
                    $query->orWhereIn('customer_id', $data_value['member_customer_ids']);
                })
                ->with(['guests', 'customer'])
                ->get();

            foreach($land_bookings_tripping_coordinators as $booking) {
                foreach($booking->guests as $guest)
                {
                    if ($booking->status === 'cancelled') {
                        $data[$sales_id]['counter_land_cancelled']++;
                        $data[$sales_id]['counter_land_total_bookings']++;
                    } else {
                        switch ($guest->status) {
                            case 'arriving':
                                $data[$sales_id]['counter_land_arriving']++;
                                $data[$sales_id]['counter_land_total_bookings']++;
                                break;

                            case 'on_premise':
                            case 'checked_in':
                                $data[$sales_id]['counter_land_arrived']++;
                                $data[$sales_id]['counter_land_total_bookings']++;
                                break;

                            case 'no_show':
                                $data[$sales_id]['counter_land_no_show']++;
                                break;

                            default:
                                $data[$sales_id]['counter_land_cancelled']++;
                                $data[$sales_id]['counter_land_total_bookings']++;
                                break;
                        }
                    }
                }
            }
                
        }

        // Log::debug($data);

        if ($download) {
            return Excel::download(
                new ReportExport('reports.booking.daily-booking-per-sd', array_values($data)),
                'report.xlsx'
            );
        }

        return response()->json([
            'status' => true,
            'data' => array_values($data),
        ]);
    }
}
