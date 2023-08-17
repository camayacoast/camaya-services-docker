<?php

namespace App\Exports\SalesAdminPortal;

use App\Models\Main\Role;
use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\CashTermPenalty;
use App\Models\RealEstate\AmortizationSchedule;
use App\Models\RealEstate\CashTermLedger;

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


class ReservationCashLedgerReports implements FromView, WithColumnWidths, ShouldAutoSize, WithEvents, WithColumnFormatting, WithStyles
{

    protected $count;
    public $reservation_number;
    public $payment_terms_type;
    public $payment_details_count = 0;
    public $sammary_details_count = 0;
    public $reservation_payment_count = 0;
    public $array_difference = 0;
    public $total_payment_details_amount = 0;
    public $total_amount_paid = 0;
    public $total_payable = 0;
    public $number_of_dp_paid = 0;
    public $number_of_dp_splits = 0;

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

                // Text Alignment
                // Put : in row side if only 1 cell needed to apply a styling
                $textCenterAlignment = [
                    'I1' => ':M4',
                    'A5' => ':H5',
                    'H6' => ':H' . ($this->payment_details_count + 5),
                    'D6' => ':D' . ($this->payment_details_count + 5),
                    'L' . ($summary_gap + 6) => ':M' . ($this->reservation_payment_count + 3),
                ];
                
                foreach( $textCenterAlignment as $column => $row ) {
                    $workSheet->getStyle($column . $row)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }

                $textRightAlignment = [
                    'C6' => ':C' . ($this->payment_details_count + 5),
                    'E6' => ':E' . ($this->payment_details_count + 5),
                    'K' . $summary_gap => ':K' . ($this->reservation_payment_count + 5),
                    'C' . ($this->payment_details_count + 9) => '',
                    'E' . ($this->payment_details_count + 9) => '',
                ];

                foreach( $textRightAlignment as $column => $row ) {
                    $workSheet->getStyle($column . $row)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }
                
                // Font weight
                $fontWeight = [
                    'A1' => '',
                    'I1' => ':K1',
                    'L1' => ':L4',
                    'B5' => ':H5',
                    'J' . $summary_gap => ':J' . ($this->reservation_payment_count + 5),
                    'E' . ($this->payment_details_count + 9) => '',
                    'F' . ($this->payment_details_count + 9) => '',
                    'B' . ($this->payment_details_count + 9) => '',
                    'C' . ($this->payment_details_count + 9) => '',
                    'L' . ($summary_gap + 6) => ':M' . ($this->reservation_payment_count + 5),
                ];

                foreach( $fontWeight as $column => $row ) {
                    $workSheet->getStyle($column . $row)->getFont()->setBold(true);
                }

                // Borders
                $borded = [
                    'A1' => ':F1',
                    'I1' => ':M2',
                    'L3' => ':M4',
                    'A5' => ':H' . ($this->payment_details_count + 5),
                    'J' . $summary_gap => ':K' . ($this->reservation_payment_count + 5),
                    'L' . ($summary_gap + 6) => ':M' . ($this->reservation_payment_count + 5),
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

                $total_amount_paid = ( $this->number_of_dp_paid == $this->number_of_dp_splits ) ? round($this->total_amount_paid) : $this->total_amount_paid;

                // Add text
                $customText = [
                    'B' . ($this->payment_details_count + 9) => 'TOTAL PAYABLE',
                    'C' . ($this->payment_details_count + 9) => number_format($this->total_payable, 2),
                    'E' . ($this->payment_details_count + 9) => number_format($total_amount_paid, 2),
                    'F' . ($this->payment_details_count + 9) => 'TOTAL PAID',
                ];

                foreach($customText as $cell => $text) {
                    $workSheet->getCell($cell)
                        ->setValueExplicit($text, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
                }

                // Merge cell
                $mergeCells = [
                    'M' . ($summary_gap + 7) => ':M' . ($this->reservation_payment_count + 5),
                    'F' . ($this->payment_details_count + 9) => ':H' . ($this->payment_details_count + 9),
                ];
                
                foreach($mergeCells as $column => $row) {
                    $workSheet->mergeCells($column . $row);
                }

                // Cell BG Color
                $coloredCells = [
                    'J' . ($this->reservation_payment_count + 5) => ':K' . ($this->reservation_payment_count + 5),
                ];

                foreach($coloredCells as $column => $row) {
                    $workSheet->getStyle($column . $row)->getFill()->applyFromArray(
                        ['fillType' => 'solid','rotation' => 0, 'color' => ['rgb' => 'F2BF05'],]
                    );
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
            // 'F' => 10,
            // 'G' => 10,
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
        
        // Reservation Payments
        $discount = $reservation->discount_amount !== null ? $reservation->discount_amount : 0;
        $discount_percentage = $reservation->discount_percentage !== null ? $reservation->discount_percentage : 0;
        $vat = $reservation->with_twelve_percent_vat ? number_format($reservation->twelve_percent_vat, 2) : '-';
        $nsp = $reservation->with_twelve_percent_vat ? $reservation->net_selling_price_with_vat : $reservation->net_selling_price;
        $reservation_fee_amount = $reservation->reservation_fee_amount !== null ? $reservation->reservation_fee_amount : 0;
        $retention_fee = ( $reservation->with_five_percent_retention_fee !== null ) ? number_format($reservation->retention_fee, 2) : '-';
        $title_transfer_fee = $nsp * 0.05;
        $this->total_payable = $reservation->total_amount_payable;
        $this->number_of_dp_splits = ( $reservation->number_of_cash_splits > 0 ) ? $reservation->number_of_cash_splits : 1;
        

        $reservation_payments = [];
        $downpayment_details = [];
        $retention_payment = [];
        $title_transfer_payment = [];
        $dp_counter = 1;
        $total_downpayment_amount = 0;
        $total_reservation_amount = 0;
        $total_title_fee_amount = 0;
        $total_retention_fee_amount = 0;
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

                if( in_array($payment['payment_type'], ['full_cash', 'partial_cash', 'split_cash']) ) {
                    $amount = $reservation->split_payment_amount != 0 ? $reservation->split_payment_amount : $this->total_payable;
                    $downpayment_details[] = [
                        'DP ' . $dp_counter,
                        Carbon::parse($reservation->reservation_fee_date)->addMonth($dp_counter)->format('m/d/Y'),
                        number_format($amount, 2),
                        Carbon::parse($payment['paid_at'])->format('m/d/Y'),
                        number_format($payment['payment_amount'], 2),
                        $payment['cr_number'] !== null ? $payment['cr_number'] : '',
                        $payment['or_number'] ? $payment['or_number'] : '',
                        $payment['bank_account_number'] ? $payment['bank_account_number'] : '',
                        ''
                    ];
                    $this->number_of_dp_paid = $dp_counter;
                    $dp_counter++;
                    $total_downpayment_amount = $amount + $total_downpayment_amount;
                    $this->total_amount_paid = $payment['payment_amount'] + $this->total_amount_paid;
                }

                /* Don't delete in case they will request to add title fee in the report
                if( $payment['payment_type'] === 'retention_fee' && $reservation->with_five_percent_retention_fee !== null ) {
                    $title_transfer_payment[] = [
                        'RETENTION FEE',
                        '',
                        number_format($reservation->retention_fee, 2),
                        Carbon::parse($payment['paid_at'])->format('m/d/Y'),
                        number_format($payment['payment_amount'], 2),
                        $payment['cr_number'] !== null ? $payment['cr_number'] : '',
                        $payment['or_number'] ? $payment['or_number'] : '',
                        $payment['bank_account_number'] ? $payment['bank_account_number'] : '',
                        ''
                    ];
                    $total_title_fee_amount = $title_transfer_fee + $total_title_fee_amount;
                    $this->total_amount_paid = $payment['payment_amount'] + $this->total_amount_paid;
                } */

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
                } */
            }
        }

        if( count($reservation_payments) <= 0 ) {
            $reservation_payments[] = [
                'RESERVATION', Carbon::parse($reservation->reservation_fee_date)->format('m/d/Y'), number_format($reservation->reservation_fee_amount, 2), '', '', '', '', '', ''
            ];
            $total_reservation_amount = $reservation->reservation_fee_amount;
        }

        if( count($downpayment_details) < 3 ) {
            $splits = ( $reservation->number_of_cash_splits > 0 ) ? $reservation->number_of_cash_splits : 1;
            $amount = $reservation->split_payment_amount != 0 ? $reservation->split_payment_amount : $reservation->total_amount_payable;
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
                        number_format($amount, 2), '', '', '', '', '', ''
                    ];
                    $c++;
                    $total_downpayment_amount = $reservation->split_payment_amount + $total_downpayment_amount;
                }
            }
        }

        /* Don't delete in case they will request to add retention fee in the report
        if( count($retention_payment) <= 0 && $reservation->with_five_percent_retention_fee !== null ) {
            $retention_payment[] = [
                'RETENTION FEE', '', number_format($reservation->retention_fee, 2), '', '', '', '', '', ''
            ];
            $total_retention_fee_amount = $reservation->retention_fee;
        } */

        /* Don't delete in case they will request to add title fee in the report
        if( count($title_transfer_payment) <= 0 ) {
            $title_transfer_payment[] = [
                'TITLE TRANSFER FEE', '', number_format($title_transfer_fee, 2), '', '', '', '', '', ''
            ];
            $total_title_fee_amount = $title_transfer_fee;
        } */
        
        $computed_total_downpayment_amount = ($this->total_payable - $total_downpayment_amount) + $total_downpayment_amount;
        $this->total_payment_details_amount = $total_reservation_amount + $computed_total_downpayment_amount + $total_retention_fee_amount + $total_title_fee_amount;
        $final_balance = ($this->total_payment_details_amount > $this->total_amount_paid) ? $this->total_payment_details_amount - $this->total_amount_paid 
            : $this->total_amount_paid - $this->total_payment_details_amount;
        $final_balance = ( $this->number_of_dp_paid == $this->number_of_dp_splits ) ? round($final_balance) : $final_balance;

        sort($reservation_payments);
        sort($downpayment_details);
        sort($retention_payment);
        sort($title_transfer_payment);
        
        $payment_details = array_merge($reservation_payments, $downpayment_details);
        $payment_details = array_merge($payment_details, $retention_payment);
        $payment_details = array_merge($payment_details, $title_transfer_payment);
        $this->payment_details_count = count($payment_details);

        $reservation_details = [
            ['Area', $reservation->area . ' SQM', '', ''],
            ['TSP', number_format($reservation->total_selling_price, 2), '', ''],
            ['DISCOUNT (' . $discount_percentage . '%)', number_format($discount, 2), '', ''],
            ['CONTRACT', number_format($nsp), '', ''],
            ['VAT (12%)', $vat, '', ''],
            ['NSP', number_format($nsp), '', ''],
            ['DP', number_format($reservation_fee_amount, 2), 'HISTORY/APPROVAL', 'DATE/TIME'],
            ['BALANCE', number_format($final_balance, 2), '', ''],
            ['RETENTION FEE', $retention_fee, '', ''],
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
            'final_balance' => 'xxxx'
        ];

        return view('exports.SalesAdminPortal.reservation_cash_ledger_reports', $data);
    }
}
