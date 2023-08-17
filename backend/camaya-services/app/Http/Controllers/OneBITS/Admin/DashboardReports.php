<?php

namespace App\Http\Controllers\OneBITS\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Trip;
use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Schedule;
use App\Models\Transportation\Passenger;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardReports extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

   
    public function __invoke(Request $request)
    {
        $totalSalesToday = $this->getTotalSalesToday();
        $totalBookingsToday = $this->getTotalBookingsToday();
        $passengerStatusCounts = $this->getPassengersStatusCounts();
    
        $response = [
            'totalSalesToday' => $totalSalesToday,
            'totalBookingsToday' => $totalBookingsToday,
            'passengerStatusCounts' => $passengerStatusCounts
        ];
    
        return response()->json($response);
    }

    public function getTotalSalesToday() {
        $total = Ticket::join('schedules', 'tickets.trip_number', '=', 'schedules.trip_number')
                        ->where('payment_status', 'paid')
                        ->whereDate('schedules.trip_date', Carbon::today())->sum('amount');
        $totalDiscount = Ticket::join('schedules', 'tickets.trip_number', '=', 'schedules.trip_number')
                        ->where('payment_status', 'paid')
                        ->whereDate('schedules.trip_date', Carbon::today())->sum('discount');
        return $total - $totalDiscount;
    }
    
    public function getTotalBookingsToday() {
        $count = Ticket::join('schedules', 'tickets.trip_number', '=', 'schedules.trip_number')
                        ->whereDate('schedules.trip_date', Carbon::today())
                        ->distinct('tickets.group_reference_number')
                        ->count('tickets.group_reference_number');

        return $count;
    }
    
    public function getPassengersStatusCounts() {
        $today = Carbon::today()->toDateString();
    
        $passengerStatus = Passenger::join('trips', 'passengers.id', '=', 'trips.passenger_id')
        ->join('schedules', 'passengers.trip_number', '=', 'schedules.trip_number')
        ->select(
            'trips.status as trip_status',
            DB::raw('COUNT(*) as count')
        )
        ->where('schedules.trip_date', $today)
        ->where('trips.ticket_reference_number', '!=', '1')
        ->groupBy('trips.status')
        ->get();
    
        $totalPassengers = $passengerStatus->sum('count');
    
        return [
            'statusCounts' => $passengerStatus,
            'total' => $totalPassengers
        ];
    }
       
}
