<?php

namespace App\Http\Controllers\OneBITS\Admin\Reports;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OneBITS\SalesReportManifest;

use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Schedule;
use App\Models\Transportation\Passenger;

use DB;

class ArrivalForeCastSummary extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke($start_date, $end_date)
    {
        $period = CarbonPeriod::create($start_date, $end_date);

        $result = [];

        foreach ($period as $key => $datePeriod) 
        {
            $date = $datePeriod->isoFormat('YYYY-MM-DD');

            $formatDate = $datePeriod->format('F d, Y');

            $result[$key] = [
                'date' => $date,
                'formatDate' => $formatDate,
                'data' => $this->getSegmentBydate($date),
                'tickets' => $this->groupTicketBySegment($date)
            ];
        }

        return response()->json($result, 200);
    }

    public function getSchedule ($date) 
    {
        return Schedule::leftJoin('routes','schedules.route_id','=','routes.id')
        ->leftJoin('locations as origin', 'routes.origin_id', '=', 'origin.id')
        ->leftJoin('locations as destination', 'routes.destination_id', '=', 'destination.id')
        ->select(
            'schedules.trip_number',
            'origin.code as origin_code',
            'destination.code as destination_code',
            'schedules.start_time',
            'schedules.end_time',
            'schedules.trip_date'
        )
        ->where('schedules.trip_date', $date)
        ->where('schedules.status', 'active')
        ->get();
    }

    public function getSegmentBydate ($date)
    {

        $schedule = $this->getSchedule($date);
        
            foreach ($schedule as $key => $sched)
            {
                    $ticket = Ticket::select(
                        'ticket_type',
                        DB::raw('COUNT(*) as passenger'),
                        DB::raw('SUM(amount) as total_rate')
                    )
                    ->where('trip_number', $sched['trip_number'])
                    ->groupBy('ticket_type')
                    ->get();

                    $schedule[$key]['ticket'] = $ticket;

                    $schedule[$key]['total'] = [
                        'all_passenger_total' => $ticket->sum('passenger'),
                        'all_total_rate' => $ticket->sum('total_rate'),
                    ];
            }
        
          return $schedule;
    }

    public function groupTicketBySegment ($date)
    {
        $segments = $this->getSegmentBydate($date);

        $result = [];

        foreach($segments as $key => $segment)
        {
            foreach($segment['ticket'] as $s)
            {

                $result[] = [
                    'ticket_type' => strtolower($s['ticket_type']),
                    'passenger' => $s['passenger'],
                    'total_rate' => $s['total_rate'],
                    'origin' => $segment['origin_code'],
                    'destination' => $segment['destination_code'],
                    'start_time' => $segment['start_time'],
                    'end_time' => $segment['end_time']
                ];
            }

        }

        $regular = collect($result)->whereIn('ticket_type', 'regular');

        $corporate = collect($result)->whereIn('ticket_type', 'corporate');

        $discounted = collect($result)->whereIn('ticket_type', 'discounted');

        $regularArray = [
            'ticket' => 'Regular',
            'passenger_count_est' => $regular->where('origin','=','EST')->sum('passenger'),
            'passenger_count_ftt' => $regular->where('origin','=','FTT')->sum('passenger'),
            'rate' => $regular->whereIn('origin',['EST','FTT'])->sum('total_rate')
        ];

        $corporateArray = [
            'ticket' => 'Corporate',
            'passenger_count_est' => $corporate->where('origin','=','EST')->sum('passenger'),
            'passenger_count_ftt' => $corporate->where('origin','=','FTT')->sum('passenger'),
            'rate' => $corporate->whereIn('origin',['EST','FTT'])->sum('total_rate')
        ];

        $discountedArray = [
            'ticket' => 'Discounted',
            'passenger_count_est' => $discounted->where('origin','=','EST')->sum('passenger'),
            'passenger_count_ftt' => $discounted->where('origin','=','FTT')->sum('passenger'),
            'rate' => $discounted->whereIn('origin',['EST','FTT'])->sum('total_rate')
        ];


        $totalArray = [
            'est_passenger_total' => intval($regularArray['passenger_count_est']) + intval($corporateArray['passenger_count_est']) + intval($discountedArray['passenger_count_est']),
            'ftt_passenger_total' => intval($regularArray['passenger_count_ftt']) + intval($corporateArray['passenger_count_ftt']) + intval($discountedArray['passenger_count_ftt']),
            'total_rate' => floatval($regularArray['rate']) + floatval($corporateArray['rate']) + floatval($discountedArray['rate']),
        ];

        return [
            'groupTicket' => $result,
            'ticketData' => [
                'regular' => $regularArray,
                'corporate' => $corporateArray,
                'discounted' => $discountedArray,
                'total' => $totalArray
            ]
        ];
        
    }
    
}
