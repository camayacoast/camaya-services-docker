<?php

namespace App\Exports\Booking;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArrivalForecastPerSegmentExport implements FromView, ShouldAutoSize, WithStyles
{

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function styles(Worksheet $sheet)
    {

        $rows = (ceil(count($this->data) / 7)) * 10;

        $sheet->getStyle('A2:AI'. ($rows+1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ]);  
    }

    public function view(): View
    {
        return view('exports.Booking.ArrivalForecastPerSegment', [
            'data' => $this->data
        ]);
    }
}
