<?php

namespace App\Exports\SalesAdminPortal;

use App\Models\Main\Role;
use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\AmortizationPenalty;
use App\Models\RealEstate\CashTermPenalty;

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


class ReservationPenaltyReports implements FromView, WithColumnWidths, ShouldAutoSize, WithEvents, WithColumnFormatting, WithStyles
{

    protected $count;
    public $reservation_number;
    public $payment_terms_type;
    public $row_count;
    public $col_count = 8;

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $workSheet = $event->sheet->getDelegate(); 
                if( $this->count > 0 ) {
                    $workSheet->getStyle('A9:H' . ($this->row_count + $this->count))
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }
                
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A6:H' . $this->row_count)->getFont()->setBold(true);
        $sheet->getStyle('A8:H8')->getFont()->setBold(true);

        // Col Styles
        for($x = 1; $x <= $this->col_count; $x++) {
            $sheet->getStyle('B'. $x)->getFont()->setBold(true);
        }

        // Records Style
        for($x = $this->col_count; $x <= ($this->col_count + $this->count); $x++) {

            $sheet->getStyle('A8:H'.$x)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ]);
        }

        $sheet->getStyle('E' . ($this->col_count + $this->count) )->getFill()->applyFromArray(
            ['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => 'F2BF05'],]
        );

        $sheet->getStyle('E' . ($this->col_count - 2) )->getFill()->applyFromArray(
            ['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => 'F2BF05'],]
        );

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

        $reservation = Reservation::where('reservation_number', $this->reservation_number)
                ->with('client.information')->get();

        $client_details = !empty($reservation[0]['client']) ? $reservation[0]['client']['first_name'] . ' ' . $reservation[0]['client']['last_name'] : '';
        $property_details = ( count($reservation) > 0 ) ? $reservation[0]['subdivision'] . '/B-' . $reservation[0]['block'] . '/L-' . $reservation[0]['lot'] : '';
        $default_discount = $reservation[0]['default_penalty_discount_percentage'];

        if( $this->payment_terms_type === 'cash' ) {
            $penalties = CashTermPenalty::where('reservation_number', $this->reservation_number)
                    ->orderBy('number', 'asc')
                    ->with('cash_term_ledger:id,due_date,amount,is_collection,number')
                    ->get();
        } else {
            $penalties = AmortizationPenalty::where('reservation_number', $this->reservation_number)
                    ->orderBy('number', 'asc')
                    ->with('amortization_schedule:id,due_date,amount,is_collection,number')
                    ->get();
        }

        $table_header = ['Months', 'Mos Past Due', 'Monthly Amortization', 'Penalty', 'Balance', 'Discount', 'Actual Payment', 'Payment Status'];
        $this->count = count($penalties);
        $this->row_count = count($table_header);

        $month = 0;
        $parent_balance = 0;
        $parent_monthly_amount = 0;
        $balance_with_penalty = 0;
        $penalty_balace = [];
        $pp = 0;
        $penalty_paid = [];

        $total_monthly_amount = 0;
        $total_penalty = 0;
        $total_actual_paid = 0;
        $total_balance = 0;

        foreach($penalties as $key => $penalty) {
            $terms = ( $this->payment_terms_type === 'in_house' ) ? $penalty['amortization_schedule'] : $penalty['cash_term_ledger'];
            $status = isset($penalty['status']) ? $penalty['status'] : null;

            if( $month != $penalty['number'] ) {

                $default_penalty_discount = ( is_null($penalty['paid_at']) && $default_discount != 0 ) ? $default_discount : $penalty['discount'];
                $penalty_discount = ( $penalty['discount'] > 0 ) ? $penalty['discount'] : $default_penalty_discount;
                $penalty_amount_with_discount = round(!is_nan( ($penalty['penalty_amount'] - ($penalty['penalty_amount'] * ($penalty['penalty_amount'] / 100))) ) ? ($penalty['penalty_amount'] - ($penalty['penalty_amount'] * ($penalty_discount / 100))) : 0, 2);

                if( is_null($status) && is_null($penalty['paid_at']) ) {
                    $b = $terms['amount'] + $penalty_amount_with_discount;
                    $balance_with_penalty = $b + $balance_with_penalty;
                    $parent_balance = $balance_with_penalty;
                    $parent_monthly_amount = $terms['amount'] + $parent_monthly_amount;
                } else {
                    $pp = $terms['amount'] + $penalty_amount_with_discount;
                }

            } else {
                $parent_monthly_amount = $parent_monthly_amount;
                $balance_with_penalty = $parent_balance + $penalty['penalty_amount'];
            }
            $penalty_balace[$key] = round($balance_with_penalty, 2);
            $penalty_paid[$key] = round($pp, 2);
            $month = $penalty['number'];

            // Update table header label based on terms
            if( $this->payment_terms_type === 'cash' ) {
                $table_header[1] = 'Split Past Due';
                $table_header[2] = 'Split Amount';
            }

            if( is_null($status) && is_null($penalty['paid_at']) ) {
                $default_penalty_discount = ( is_null($penalty['paid_at']) && $default_discount != 0 ) ? $default_discount : $penalty['discount'];
                $penalty_discount = ( $penalty['discount'] > 0 ) ? $penalty['discount'] : $default_penalty_discount;
                $penalty_amount = (float) $penalty['penalty_amount'];
                $penalty_amount_with_discount = round(!is_nan( ($penalty_amount - ($penalty_amount * ($penalty_discount / 100))) ) ? ($penalty_amount - ($penalty_amount * ($penalty_discount / 100))) : 0, 2);
                $total_penalty = $penalty_amount_with_discount + $total_penalty;
            }
            

            $total_monthly_amount = $parent_monthly_amount;
            $total_actual_paid = $penalty['amount_paid'] + $total_actual_paid;
            $total_balance = $balance_with_penalty;
            
        }

        $parent_id = 0;
        $amortization_number = 0;
        $penalty_data = [];
        foreach($penalties as $key => $penalty) {

            $status = isset($penalty['status']) ? $penalty['status'] : null;

            $penalty_data[$key]['due_date'] = ( $this->payment_terms_type === 'in_house' ) ? 
                Carbon::parse($penalty['amortization_schedule']['due_date'])->format('m/d/Y') : 
                Carbon::parse($penalty['cash_term_ledger']['due_date'])->format('m/d/Y');
            
            $penalty_data[$key]['number'] = ( $this->payment_terms_type === 'in_house' ) ? 
                $penalty['amortization_schedule']['number'] : 
                $penalty['cash_term_ledger']['number'];

            if( $amortization_number != $penalty['number'] ) {
                $parent_id = $penalty['id'];
                $amortization_number = $penalty['number'];
            }

            if( $parent_id == $penalty['id']  ) {
                $penalty_data[$key]['amount'] = ( $this->payment_terms_type === 'in_house' ) ? 
                    number_format($penalty['amortization_schedule']['amount'], 2) : 
                    number_format($penalty['cash_term_ledger']['amount'], 2);
            } else {
                $penalty_data[$key]['amount'] = '-';
            }

            $default_penalty_discount = ( is_null($penalty['paid_at']) && $default_discount != 0 ) ? $default_discount : $penalty['discount'];
            $discount = ( $penalty['discount'] > 0 ) ? $penalty['discount'] : $default_penalty_discount;

            // if( $discount == 0 && $penalty['penalty_amount'] > $penalty['amount_paid'] && !is_null($penalty['amount_paid']) ) {
            //     $discount = round( ((($penalty['penalty_amount'] - $penalty['amount_paid']) / $penalty['penalty_amount']) * 100), 5);
            // }

            $default_penalty_discount = ( is_null($penalty['paid_at']) && $default_discount != 0 ) ? $default_discount : $penalty['discount'];
            $penalty_discount = ( $penalty['discount'] > 0 ) ? $penalty['discount'] : $default_penalty_discount;
            $penalty_amount_with_discount = round(!is_nan( ($penalty['penalty_amount'] - ($penalty['penalty_amount'] * ($penalty['penalty_amount'] / 100))) ) ? ($penalty['penalty_amount'] - ($penalty['penalty_amount'] * ($penalty_discount / 100))) : 0, 2);

            if( $status == null ) {
                $penalty_data[$key]['penalty_amount'] = number_format($penalty_amount_with_discount, 2);
                $penalty_data[$key]['balance'] = !is_null($penalty['paid_at']) ? number_format($penalty_paid[$key], 2) : number_format($penalty_balace[$key], 2);
                $penalty_data[$key]['discount'] = $discount . '%';
                $penalty_data[$key]['actual_payment'] = ($penalty['amount_paid'] !== null) ? number_format($penalty['amount_paid'], 2) : 0;
                $penalty_data[$key]['status'] = ($penalty['paid_at'] !== null) ? 'Paid' : 'Not Paid';
            } else {
                $penalty_data[$key]['penalty_amount'] = '-';
                $penalty_data[$key]['balance'] = '-';
                $penalty_data[$key]['discount'] = '-';
                $penalty_data[$key]['actual_payment'] = 0;
                $penalty_data[$key]['status'] = 'Void';
            }

            // $penalty_data[$key]['penalty_amount'] = number_format($penalty_amount_with_discount, 2);
            // $penalty_data[$key]['balance'] = number_format($penalty_balace[$key], 2);
            // $penalty_data[$key]['discount'] = $discount . '%';
            // $penalty_data[$key]['actual_payment'] = ($penalty['amount_paid'] !== null) ? number_format($penalty['amount_paid'], 2) : 0;

            // $penalty_data[$key]['status'] = ($penalty['paid_at'] !== null) ? 'Paid' : 'Not Paid';
            
        }

        $summary = [
            ['', '', '', '', '', '', '', ''],
            ['', 'Client:', $client_details, '', '', '', '', ''],
            ['', 'Phase/Blk/Lot', $property_details, '', '', '', '', ''],
            ['', '', '', '', '', '', '', ''],
            ['', 'TOTAL', number_format($total_monthly_amount, 2), number_format($total_penalty, 2), number_format($total_balance, 2), '', number_format($total_actual_paid, 2), ''],
            ['', '', '', '', '', '', '', ''],
        ];

        return view('exports.SalesAdminPortal.reservation_penalty_reports', [
            'payment_term' => $this->payment_terms_type,
            'reservation_number' => $this->reservation_number,
            'title' => 'Computation for Interest and Penalties',
            'table_header' => $table_header,
            'penalty_data' => $penalty_data,
            'summaries' => $summary
        ]);
    }
}
