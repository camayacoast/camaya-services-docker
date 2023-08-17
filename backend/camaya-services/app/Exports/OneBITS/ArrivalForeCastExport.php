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
use App\Models\Transportation\Passenger;

class ArrivalForeCastExport implements FromView, WithColumnWidths, ShouldAutoSize, WithDrawings
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
            'A' => 5,
            'B' => 15,
            'C' => 15,
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
       
        $start_date = Carbon::parse($this->startDate)->setTimezone('Asia/Manila')->format('Y-m-d');
        $end_date = Carbon::parse($this->endDate)->setTimezone('Asia/Manila')->format('Y-m-d');

        $passengers = Passenger::leftJoin('schedules', 'passengers.trip_number', '=', 'schedules.trip_number')
                        ->leftJoin('trips', 'passengers.id', '=', 'trips.passenger_id')
                        ->leftJoin('routes', 'schedules.route_id', '=', 'routes.id')
                        ->leftJoin('locations as origin', 'routes.origin_id', '=', 'origin.id')
                        ->leftJoin('locations as destination', 'routes.destination_id', '=', 'destination.id')
                        ->select(
                            'passengers.*',
                            'schedules.id as schedule_id',
                            'schedules.trip_date',
                            'schedules.start_time',
                            'schedules.end_time',
                            'origin.code as origin_code',
                            'destination.code as destination_code',
                        )
                        ->whereBetween('schedules.trip_date', [$start_date, $end_date])
                        ->where('trips.ticket_reference_number', '!=', '1')
                        ->with(['trip' => function ($q) {
                            $q->where('ticket_reference_number', '!=', '1');
                        }])
                        ->with('ticket')
                        ->get();

        $statuses = ['pending', 'checked_in', 'boarded', 'no_show', 'cancelled'];

        $statusCounts = $passengers->groupBy('trip.status')
            ->map(function ($passengers) {
                return $passengers->count();
            })
            ->union(array_fill_keys($statuses, 0))
            ->toArray();


            return view('exports.OneBITS.arrivalForecastDownloadView', [
                'passengers' => $passengers,
                'statusCounts' => $statusCounts,
                'startdate' => $start_date,
                'total' => $passengers->count()
            ]);

    }
}
