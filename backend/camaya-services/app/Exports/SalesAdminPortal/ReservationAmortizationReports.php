<?php

namespace App\Exports\SalesAdminPortal;

use App\Models\Main\Role;
use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\CashTermPenalty;
use App\Models\RealEstate\AmortizationSchedule;

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


class ReservationAmortizationReports implements FromView, WithColumnWidths, ShouldAutoSize, WithEvents, WithColumnFormatting, WithStyles
{

    protected $count;
    public $reservation_number;
    public $payment_terms_type;
    public $payment_details_count = 0;
    public $sammary_details_count = 0;
    public $reservation_payment_count = 0;
    public $array_difference = 0;
    public $amortization_count = 0;
    public $total_payment_details_amount = 0;
    public $total_amount_paid = 0;
    public $total_amortization_paid = 0;
    public $number_of_years = 0;
    public $amortization_amount = 0;

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                
                // Initialize sheet event deligation
                $workSheet = $event->sheet->getDelegate(); 

                // (initial summary top gap count + payment details total count) - ( summary total count - initial summary top gap count )
                $summary_gap = (5 + $this->payment_details_count) - ($this->sammary_details_count - 5);
                // Static 6 if payment details is < the summary details
                $summary_gap = ( $this->payment_details_count <= $this->sammary_details_count ) ? 6 : $summary_gap;

                // Amortization table startpoint and endpoint

                $amortizationCellAdder = ( $this->payment_details_count > $this->sammary_details_count ) ? 3 : 0;

                $amortizationBorderStart = $summary_gap + $this->sammary_details_count;
                $amortizationBorderEnd = $summary_gap + $this->sammary_details_count + $this->amortization_count;

                // Text Alignment
                // Put : in row side if only 1 cell needed to apply a styling
                $textCenterAlignment = [
                    'J1' => ':N4',
                    'A5' => ':H5',
                    'H6' => ':H' . ($this->payment_details_count + 5),
                    'D6' => ':D' . ($this->payment_details_count + 5),
                    'A' . $amortizationBorderStart => ':N' . $amortizationBorderStart,
                    'A' . ($amortizationBorderStart + 1) => ':A' . $amortizationBorderEnd,
                    'B' . ($amortizationBorderStart + 1) => ':B' . $amortizationBorderEnd,
                    'D' . ($amortizationBorderStart + 1) => ':D' . $amortizationBorderEnd,
                    'M' . ($amortizationBorderStart + 1) => ':M' . $amortizationBorderEnd,
                    'M' . ($amortizationBorderStart - 1) => '',
                    'M' . ($amortizationBorderStart - 4) => '',
                ];
                
                foreach( $textCenterAlignment as $column => $row ) {
                    $workSheet->getStyle($column . $row)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }

                $textRightAlignment = [
                    'C6' => ':C' . ($this->payment_details_count + 5),
                    'E6' => ':E' . ($this->payment_details_count + 5),
                    'L' . $summary_gap => ':L' . ($this->reservation_payment_count + 5),
                    'C' . ($amortizationBorderStart + 1) => ':C' . $amortizationBorderEnd,
                    'E' . ($amortizationBorderStart + 1) => ':E' . $amortizationBorderEnd,
                    'F' . ($amortizationBorderStart + 1) => ':H' . $amortizationBorderEnd,
                    'J' . ($amortizationBorderStart + 1) => ':L' . $amortizationBorderEnd,
                    'N' . ($amortizationBorderStart + 1) => ':N' . $amortizationBorderEnd,
                    'C' . ($this->payment_details_count + 6) => '',
                    'E' . ($this->payment_details_count + 6) => '',
                ];

                foreach( $textRightAlignment as $column => $row ) {
                    $workSheet->getStyle($column . $row)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }
                
                // Font weight
                $fontWeight = [
                    'A1' => '',
                    'J1' => ':L1',
                    'M1' => ':M4',
                    'B5' => ':H5',
                    'K' . $summary_gap => ':K' . ($this->reservation_payment_count + 5),
                    'A' . $amortizationBorderStart => ':N' . $amortizationBorderStart,
                    'A' . ($this->payment_details_count + 6) => '',
                    'C' . ($this->payment_details_count + 6) => '',
                    'E' . ($this->payment_details_count + 6) => '',
                    'M' . ($amortizationBorderStart - 4) => '',
                ];

                foreach( $fontWeight as $column => $row ) {
                    $workSheet->getStyle($column . $row)->getFont()->setBold(true);
                }

                // Borders
                $borded = [
                    'A1' => ':G1',
                    'J1' => ':N2',
                    'M3' => ':N4',
                    'A5' => ':H' . ($this->payment_details_count + 5),
                    'K' . $summary_gap => ':L' . ($this->reservation_payment_count + 5),
                    'A' . $amortizationBorderStart => ':N' . $amortizationBorderEnd,
                ];

                foreach( $borded as $column => $row ) {
                    $workSheet->getStyle($column . $row)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                                'color' => ['argb' => '000000'],
                            ],
                        ],
                    ]);
                }

                // Add text
                $total_paid = $this->total_amount_paid + $this->total_amortization_paid;
                $year_label = ($this->number_of_years > 1) ? ' YEARS TO PAY' : ' YEAR TO PAY';
                $customText = [
                    'A' . ($this->payment_details_count + 6) => 'TOTAL',
                    'C' . ($this->payment_details_count + 6) => number_format($this->total_payment_details_amount, 2),
                    'E' . ($this->payment_details_count + 6) => number_format($this->total_amount_paid, 2),
                    'M' . ($amortizationBorderStart - 1) => number_format($this->amortization_amount, 2) . '   ' . $this->number_of_years . $year_label, 
                    'M' . ($amortizationBorderStart - 4) => 'TOTAL PAID ' . ' ' . number_format($total_paid, 2),
                ];

                foreach($customText as $cell => $text) {
                    $workSheet->getCell($cell)
                        ->setValueExplicit($text, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
                }

                // Merge cell
                $mergeCells = [
                    'M' . ($amortizationBorderStart - 1) => ':N' . ($amortizationBorderStart - 1),
                    'M' . ($amortizationBorderStart - 4) => ':N' . ($amortizationBorderStart - 4),
                ];
                
                foreach($mergeCells as $column => $row) {
                    $workSheet->mergeCells($column . $row);
                }

            }
        ];
    }

    public function styles(Worksheet $sheet)
    {

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
            ->with('client.information')
            ->with('agent.team_member_of.team')
            ->with('co_buyers.details')
            ->with('promos')
            ->with('attachments')
            ->with('referrer')
            ->with('referrer_property_details')
            ->with(['amortization_schedule.penalties', 'amortization_schedule.payments'])
            ->with(['cash_term_ledger.penalties', 'cash_term_ledger.payments'])
            ->with('sales_manager')
            ->with('sales_director')
            ->with('amortization_fees.added_by')
            ->with(['payment_details' => function($query) {
                $query->where('is_verified', 1)->orderBy('id', 'asc');
            }])->first();

        $client_details = !empty($reservation->client) > 0  ? $reservation->client['first_name'] . ' ' . $reservation->client['last_name'] : '';
        $agent = !empty($reservation->agent) > 0 ? $reservation->agent['first_name'] . ' ' . $reservation->agent['last_name'] : '';
        $sales_manager = !empty($reservation->sales_manager) > 0 ? $reservation->sales_manager['first_name'] . ' ' . $reservation->sales_manager['last_name ']: '';
        $sales_director = !empty($reservation->sales_director) > 0 ? $reservation->sales_director['first_name'] . ' ' . $reservation->sales_director['last_name'] : '';
        $this->number_of_years = $reservation->number_of_years;

        // Amortization
        $amortization_schedules = AmortizationSchedule::collections($reservation);

        $schedules = [];
        $final_balance = 0;
        $sched_number = 0;
        foreach( $amortization_schedules as $key => $schedule ) {

            $penalty_payment = '';
            if( $schedule->payments->count() > 0 ) {
                foreach( $schedule->payments as $k => $p ) {
                    if( $p['payment_type'] == 'penalty' ) {
                        $penalty_payment = $p['payment_amount'];
                    }
                }
            }

            $schedules[$key] = [
                ($schedule['number'] !== $sched_number) ? $schedule['number'] : '',
                (($schedule['excess_payment'] !== 1 && $schedule['number'] !== $sched_number) || ($schedule['excess_payment'] !== 1 && is_null($schedule['paid_at']))) ? Carbon::parse($schedule['due_date'])->format('m/d/Y') : '',
                ($schedule['excess_payment'] !== 1 && $schedule['number'] !== $sched_number || ($schedule['excess_payment'] !== 1 && is_null($schedule['paid_at']))) ? number_format($schedule['amount'], 2) : '',
                ($schedule['date_paid'] !== null && $schedule['excess_payment'] !== 1) ? Carbon::parse($schedule['date_paid'])->format('m/d/Y') : '',
                number_format($schedule['amount_paid'], 2),
                $penalty_payment,
                $schedule['pr_number'],
                $schedule['or_number'],
                $schedule['account_number'],
                number_format($schedule['principal'], 2),
                number_format($schedule['interest'], 2),
                number_format($schedule['balance'], 2),
                $schedule['remarks'],
                Carbon::now()->format('m/d/Y'),
            ];
            $final_balance = $schedule['balance'];
            $this->total_amortization_paid = $schedule['amount_paid'] + $this->total_amortization_paid;
            $this->amortization_amount = $schedule['amount'];
            $this->amortization_count++;
            $sched_number = $schedule['number'];
        }
        
        // Reservation Payments
        $discount = $reservation->discount_amount !== null ? $reservation->discount_amount : 0;
        $discount_percentage = $reservation->discount_percentage !== null ? $reservation->discount_percentage : 0;
        $vat = $reservation->with_twelve_percent_vat ? number_format($reservation->twelve_percent_vat, 2) : '-';
        $nsp = $reservation->with_twelve_percent_vat ? $reservation->net_selling_price_with_vat : $reservation->net_selling_price;
        $downpayment_amount = $reservation->downpayment_amount !== null ? $reservation->downpayment_amount : 0;
        $title_transfer_fee = $nsp * 0.05;

        $reservation_payments = [];
        $downpayment_details = [];
        $title_transfer_payment = [];
        $dp_counter = 1;
        $total_downpayment_amount = 0;
        $total_reservation_amount = 0;
        $total_title_fee_amount = 0;
        if( !empty($reservation->payment_details) ) {
            foreach($reservation->payment_details as $key => $payment) {

                if( $payment['payment_type'] === 'reservation_fee_payment' ) {
                    $reservation_payments[] = [
                        'RESERVATION',
                        Carbon::parse($reservation->reservation_fee_date)->format('m/d/Y'),
                        number_format($reservation->reservation_fee_amount, 2),
                        Carbon::parse($payment['paid_at'])->format('m/d/Y'),
                        number_format($payment['payment_amount'], 2),
                        $payment['cr_number'] !== null ? $payment['cr_number'] : '',
                        $payment['or_number'] ? $payment['or_number'] : '',
                        $payment['bank_account_number'] ? $payment['bank_account_number'] : '',
                        ''
                    ];
                    $total_reservation_amount = $reservation->reservation_fee_amount + $total_reservation_amount;
                    $this->total_amount_paid = $payment['payment_amount'] + $this->total_amount_paid;
                }

                if( $payment['payment_type'] === 'downpayment' ) {
                    $downpayment_details[] = [
                        'DP ' . $dp_counter,
                        Carbon::parse($reservation->reservation_fee_date)->addMonth($dp_counter)->format('m/d/Y'),
                        number_format($reservation->downpayment_amount, 2),
                        Carbon::parse($payment['paid_at'])->format('m/d/Y'),
                        number_format($payment['payment_amount'], 2),
                        $payment['cr_number'] !== null ? $payment['cr_number'] : '',
                        $payment['or_number'] ? $payment['or_number'] : '',
                        $payment['bank_account_number'] ? $payment['bank_account_number'] : '',
                        ''
                    ];
                    $dp_counter++;
                    $total_downpayment_amount = $reservation->downpayment_amount + $total_downpayment_amount;
                    $this->total_amount_paid = $payment['payment_amount'] + $this->total_amount_paid;
                }

                /* Don't delete in case they will request to add title fee in the report
                if( $payment['payment_type'] === 'title_fee' ) {
                    $title_transfer_payment[] = [
                        'TITLE TRANSFER FEE',
                        '',
                        number_format($title_transfer_fee, 2),
                        Carbon::parse($payment['paid_at'])->format('m/d/Y'),
                        number_format($payment['payment_amount'], 2),
                        $payment['cr_number'] !== null ? $payment['cr_number'] : '',
                        $payment['or_number'] ? $payment['or_number'] : '',
                        $payment['bank_account_number'] ? $payment['bank_account_number'] : '',
                        ''
                    ];
                    $total_title_fee_amount = $title_transfer_fee + $total_title_fee_amount;
                    $this->total_amount_paid = $payment['payment_amount'] + $this->total_amount_paid;
                }
                */
            }
        }       
        
        if( count($reservation_payments) <= 0 ) {
            $reservation_payments[] = [
                'RESERVATION', Carbon::parse($reservation->reservation_fee_date)->format('m/d/Y'), number_format($reservation->reservation_fee_amount, 2), '', '', '', '', '', ''
            ];
            $total_reservation_amount = $reservation->reservation_fee_amount;
        }

        if( count($downpayment_details) < 3 ) {
            $splits = ( $reservation->number_of_downpayment_splits > 0 ) ? $reservation->number_of_downpayment_splits : 1;
            $date = $reservation->reservation_fee_date;
            $c = 1;
            for($x = 0; $x <  $splits; $x++) {
                if( isset($downpayment_details[$x]) ) {
                    $downpayment_details[$x] = $downpayment_details[$x];
                    $date = $downpayment_details[$x][1];
                    $c++;
                } else {
                    $downpayment_details[$x] = [
                        'DP ' . $c, 
                        Carbon::parse($date)->addMonth($x)->format('m/d/Y'), 
                        number_format($reservation->downpayment_amount, 2), '', '', '', '', '', ''
                    ];
                    $c++;
                    $total_downpayment_amount = $reservation->downpayment_amount + $total_downpayment_amount;
                }
            }
        }

        /* Don't delete in case they will request to add title fee in the report
        if( count($title_transfer_payment) <= 0 ) {
            $title_transfer_payment[] = [
                'TITLE TRANSFER FEE', '', number_format($title_transfer_fee, 2), '', '', '', '', '', ''
            ];
            $total_title_fee_amount = $title_transfer_fee;
        } */
         
        $this->total_payment_details_amount = $total_reservation_amount + $total_downpayment_amount + $total_title_fee_amount;

        sort($reservation_payments);
        sort($downpayment_details);
        sort($title_transfer_payment);
        
        $payment_details = array_merge($reservation_payments, $downpayment_details);
        $payment_details = array_merge($payment_details, $title_transfer_payment);
        $this->payment_details_count = count($payment_details);

        $reservation_details = [
            ['', 'Area', $reservation->area . ' SQM', '', ''],
            ['', 'TSP', number_format($reservation->total_selling_price, 2), '', ''],
            ['', 'DISCOUNT (' . $discount_percentage . '%)', number_format($discount, 2), '', ''],
            ['', 'VAT (12%)', $vat, '', ''],
            ['', 'NSP', number_format($nsp), '', ''],
            ['', 'DP', number_format($downpayment_amount, 2), '', ''],
            ['', 'BALANCE', number_format($final_balance, 2), '', '']
        ];

        if( count($payment_details) > count($reservation_details) ) {
            $this->sammary_details_count = count($reservation_details);
            $this->array_difference = (count($payment_details) - count($reservation_details) );
            $summary_map = $this->array_difference + 3;
            for( $x = 0; $x <= $summary_map; $x++ ) {
                array_unshift($reservation_details, ['', '', '', '']);
            }
        } else {
            $this->sammary_details_count = count($reservation_details);
        }
        

        $content_dependency_count = (count($reservation_details) >= count($payment_details)) ? count($reservation_details) : count($payment_details);

        $reservation_payments = [];
        for( $x = 0; $x < $content_dependency_count; $x++ ) {
            $pd = isset($payment_details[$x]) ? $payment_details[$x] : ['', '', '', '', '', '', '', '', ''];
            $rd = isset($reservation_details[$x]) ? $reservation_details[$x] : ['', '', '', ''];
            $reservation_payments[$x] = array_merge($pd, $rd);
        }
        $this->reservation_payment_count = count($reservation_payments);

        $data = [
            'client' =>  strtoupper($client_details),
            'subdivision' => $reservation->subdivision_name,
            'block' => $reservation->block,
            'lot' => $reservation->lot,
            'agent' => $agent,
            'sales_manager' => $sales_manager,
            'sales_director' => $sales_director,
            'payment_details' => $reservation_payments,
            'final_balance' => $final_balance,
            'schedules' => $schedules,
        ];

        return view('exports.SalesAdminPortal.reservation_amortization_reports', $data);
    }
}
