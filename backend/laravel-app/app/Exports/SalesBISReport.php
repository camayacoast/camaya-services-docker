<?php

namespace App\Exports;

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
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class SalesBISReport implements FromView, WithColumnWidths, ShouldAutoSize, WithEvents, WithStyles
    // , WithDrawings
{

    // protected $trip_number;
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
                $workSheet->freezePane('F4'); // freezing here
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,          
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('D3:BX'.($this->count+3))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ]);  
            
        // $sheet->getStyle('A1:X1')->getFont()->setBold(true);  
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

        $reservations = Reservation::with('client.information.attachments')
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

        $reservations2 = Reservation::with('client.information.attachments')
                                ->with('agent.team_member_of.team.teamowner')
                                ->with('sales_director')
                                ->with('sales_manager')
                                ->with('co_buyers.details')
                                ->with('promos')
                                ->with('attachments')
                                ->with('amortization_schedule.penalties')
                                ->where('client_number', '=', '')
                                ->get();

        $r = $reservations->merge($reservations2);

        $this->count = count($r);

        return view('exports.SalesAdminPortal.sales_bis_report', [
            'reservations' => $r
        ]);
    }
}
