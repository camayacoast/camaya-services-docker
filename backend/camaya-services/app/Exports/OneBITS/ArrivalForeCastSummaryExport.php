<?php

namespace App\Exports\OneBITS;

use App\Models\Main\Role;
use App\Models\Transportation\Trip;
use App\Models\Transportation\Schedule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Transportation\Passenger;
use App\Models\OneBITS\Ticket;

use DB;

class ArrivalForeCastSummaryExport implements FromView, WithColumnWidths, ShouldAutoSize, WithDrawings
{

    protected $startDate;

    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 26,
            'C' => 27,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 25,
            'H' => 15,
            'I' => 20,
            'J' => 20,           
        ];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('logo');
        $drawing->setPath(public_path('images/1bits-logo.png'));
        $drawing->setCoordinates('G1');
        $drawing->setOffsetX(150);
        $drawing->setHeight(70);

        return $drawing;
    }

    public function view(): View
    {

        $period = CarbonPeriod::create($this->startDate, $this->endDate);

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

        return view('exports.OneBITS.arrivalForecastSummaryDownloadView', [
            'results' => $result,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate
        ]);
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
