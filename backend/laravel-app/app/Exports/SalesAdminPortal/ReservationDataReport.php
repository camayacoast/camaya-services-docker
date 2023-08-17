<?php

namespace App\Exports\SalesAdminPortal;

use App\Models\Main\Role;
use App\Models\RealEstate\Reservation;

// use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
// use Maatwebsite\Excel\Concerns\WithDrawings;
// use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class ReservationDataReport implements FromView, WithColumnWidths, ShouldAutoSize, WithEvents, WithColumnFormatting, WithStyles
    // , WithDrawings
{

    protected $count;

    // public function __construct($trip_number, $status)
    // {
    //     $this->trip_number = $trip_number;
    //     $this->status = $status;
    // }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $workSheet = $event->sheet->getDelegate();
                // $workSheet->freezePane('F4'); // freezing here
                // $workSheet->getStyle('A1:X')->applyFromArray([
                //     'borders' => [
                //         'allBorders' => [
                //             'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                //             'color' => ['argb' => '000000'],
                //         ],
                //     ],
                // ]);    
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:X'.($this->count+1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ]);  
            
        $sheet->getStyle('A1:X1')->getFont()->setBold(true);  
    }

    public function columnWidths(): array
    {
        return [
            // 'A' => 5,          
        ];
    }

    public function columnFormats(): array
    {
        return [
            // 'O' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC,
            // 'U' => ,
            // 'V' => ,
        ];
    }

    // public function drawings()
    // {
        // $drawing = new Drawing();
        // $drawing->setName('logo');
        // $drawing->setPath(public_path('images/magic-leaf-logo.png'));
        // $drawing->setCoordinates('G1');
        // $drawing->setOffsetX(150);
        // $drawing->setHeight(70);

        // return $drawing;
    // }

    public function view(): View
    {

        $reservations = Reservation::with('client')
                                ->with('agent.team_member_of.team.teamowner')
                                ->with('sales_director')
                                ->with('sales_manager')
                                ->with('co_buyers.details')
                                ->with('promos')
                                ->with('attachments')
                                ->with('amortization_schedule.penalties')
                                ->orderBy('client_number', 'ASC')
                                ->where('client_number', '!=', '')
                                ->get();

        $reservations2 = Reservation::with('client')
                                ->with('agent.team_member_of.team.teamowner')
                                ->with('sales_director')
                                ->with('sales_manager')
                                ->with('co_buyers.details')
                                ->with('promos')
                                ->with('attachments')
                                ->with('amortization_schedule.penalties')
                                ->where('client_number', '=', '')
                                ->get();

        // Include co-buyers
        

        $r = $reservations->merge($reservations2);

        $this->count = count($r);

        return view('exports.SalesAdminPortal.reservation_data_report', [
            'reservations' => $r
        ]);
    }
}
