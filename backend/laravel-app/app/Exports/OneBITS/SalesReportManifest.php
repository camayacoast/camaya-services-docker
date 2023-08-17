<?php

namespace App\Exports\OneBITS;

use App\Models\Main\Role;
use App\Models\Transportation\Trip;
use App\Models\Transportation\Schedule;
// use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Carbon\Carbon;
use App\Models\Transportation\Passenger;

class SalesReportManifest implements FromView, WithColumnWidths, ShouldAutoSize, WithDrawings
{

    protected $start_date;
    protected $end_date;
    protected $booking;

    public function __construct($start_date, $end_date, $bookings)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->bookings = $bookings;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5, 
            'B' => 25, 
            'C' => 30, 
            'D' => 20, 
            'E' => 20, 
            'F' => 20, 
            'G' => 15,    
            'H' => 15,         
            'I' => 15,           
            'J' => 25,           
            'K' => 25,           
            'L' => 25,           
            'M' => 25 ,
            'N' => 25           
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
        
        return view('exports.OneBITS.oneBITSSalesReport', [
            'bookings' => $this->bookings,
            'start_date' => Carbon::parse($this->start_date)->format('F d, Y'),
            'end_date' => Carbon::parse($this->end_date)->format('F d, Y')
        ]);
    }
}
