<?php

namespace App\Exports\SalesAdminPortal;

use App\Models\Main\Role;
use App\Models\RealEstate\LotInventory;
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


class InventoryStatusReport implements FromView, WithColumnWidths, ShouldAutoSize, WithEvents, WithColumnFormatting, WithStyles
    // , WithDrawings
{

    protected $count;
    public $property_type;

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
                $workSheet->freezePane('A4'); // freezing here
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
        $sheet->getStyle('B3:M3')->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ]);  
            
        $sheet->getStyle('B3:M3')->getFont()->setBold(true);  
    }

    public function columnWidths(): array
    {
        return [
            'M' => 15,          
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

        $lots = LotInventory::take(100)->where('property_type', $this->property_type)->get();

        $this->count = count($lots);

        foreach( $lots as $key => $lot ) {

            $client_name = '';
            $reservation = Reservation::where('subdivision', $lot['subdivision'])
                ->where('lot', $lot['lot'])
                ->where('area', $lot['area'])
                ->where('type', $lot['type'])
                ->whereNotIn('status', ['draft'])
                ->with('client.information')
                ->first();

            if( $reservation ) {
                $client_name = ( isset($reservation->client) ) ? $reservation->client->first_name . ' ' . $reservation->client->last_name : ''; 
            }

            $lots[$key]['client_name'] = $client_name;

            $tsp_rounded = round($lot['price_per_sqm'] * $lot['area']);
            $tsp_floor = floor($lot['price_per_sqm'] * $lot['area']);

            $tsp_final = ($tsp_rounded % 10 > 0) ? ($tsp_rounded - ($tsp_rounded % 10)) : $tsp_rounded;

            $lots[$key]['tsp'] = $tsp_final;

        }

        return view('exports.SalesAdminPortal.inventory_status_report', [
            'property_type' => $this->property_type,
            'lots' => $lots
        ]);
    }
}
