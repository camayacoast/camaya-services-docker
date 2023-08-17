<?php

namespace App\Exports\SalesAdminPortal;

use App\Models\Main\Role;

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

use Carbon\Carbon;


class InventoryTemplate implements FromView, WithColumnWidths, ShouldAutoSize, WithEvents, WithColumnFormatting, WithStyles
{

    public $property_type;

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $workSheet = $event->sheet->getDelegate();
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // $sheet->getStyle('A1:G1')->getFill()->applyFromArray(
        //     ['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => 'F7EB00'],]
        // );
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

    public function view(): View
    {
        $headers = ['phase', 'subdivision_name', 'subdivision', 'block', 'lot', 'client_number', 'status2', 'status', 'color', 'remarks', 'area', 'type', 'price_per_sqm'];

        if( $this->property_type === 'condo'  ) {
            foreach( $headers as $key => $header ) {
                if( $header == 'subdivision_name' ) {
                    $headers[$key] = 'project';
                }
                if( $header == 'subdivision' ) {
                    $headers[$key] = 'project_acronym';
                }
                if( $header == 'price_per_sqm' ) {
                    $headers[$key] = 'tsp';
                }
                if( $header == 'lot' ) {
                    $headers[$key] = 'unit';
                }
                if( $header == 'block' ) {
                    $headers[$key] = 'bldg';
                }
            }
        }

        $sample_lot_record = [
            ['1', 'SAMPLE TEAM', 'ST', '69', '1', '1001', 'Engr Res', 'available', 'Blue', 'your remarks here', '401', 'CORNER', '21500'],
            ['1', 'SAMPLE TEAM', 'ST', '70', '2', '1002', 'Cash', 'not_saleable', 'Blue', 'your remarks here', '401', 'CORNER', '32500'],
            ['1', 'SAMPLE TEAM', 'ST', '71', '3', '1003', 'Current', 'pending_migration', 'Blue', 'your remarks here', '401', 'CORNER', '15500'],
            ['1', 'SAMPLE TEAM', 'ST', '72', '4', '1004', 'Available', 'reserved', 'Blue', 'your remarks here', '401', 'CORNER', '66500'],
            ['1', 'SAMPLE TEAM', 'ST', '73', '5', '1005', 'Ofc Res', 'for_review', 'Blue', 'your remarks here', '401', 'CORNER', '16500'],
            ['1', 'SAMPLE TEAM', 'ST', '73', '6', '1006', 'Special', 'sold', 'Blue', 'your remarks here', '401', 'CORNER', '12200'],
        ];

        $sample_condo_record = [
            ['Sample 2 (Bldg 1)', 'SAMPLE Villa Building', 'SV', '1', '101', '1001', 'Engr Res', 'available', 'Blue', 'your remarks here', '401', 'CORNER', '9750000'],
            ['Sample 2 (Bldg 1)', 'SAMPLE Villa Building', 'SV', '2', '102', '1002', 'Cash', 'not_saleable', 'Blue', 'your remarks here', '401', 'CORNER', '5750000'],
            ['Sample 2 (Bldg 1)', 'SAMPLE Villa Building', 'SV', '3', '103', '1003', 'Current', 'pending_migration', 'Blue', 'your remarks here', '401', 'CORNER', '3250000'],
            ['Sample 2 (Bldg 1)', 'SAMPLE Villa Building', 'SV', '4', '104', '1004', 'Available', 'reserved', 'Blue', 'your remarks here', '401', 'CORNER', '1250000'],
            ['Sample 2 (Bldg 1)', 'SAMPLE Villa Building', 'SV', '5', '105', '1005', 'Ofc Res', 'for_review', 'Blue', 'your remarks here', '401', 'CORNER', '3500000'],
            ['Sample 2 (Bldg 1)', 'SAMPLE Villa Building', 'SV', '6', '106', '1006', 'Special', 'sold', 'Blue', 'your remarks here', '401', 'CORNER', '2700000'],
        ];

        return view('exports.SalesAdminPortal.inventory_template', [
            'headers' => $headers,
            'records' => ($this->property_type == 'lot') ? $sample_lot_record : $sample_condo_record
        ]);
    }
}
