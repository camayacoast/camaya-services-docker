<?php

namespace App\Exports\SalesAdminPortal;

use App\Models\Main\Role;
use App\Models\RealEstate\RealEstatePayment;
use App\Models\RealEstate\RealEstatePaymentStatus;

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

class UnidentifiedReport implements FromView, WithColumnWidths, ShouldAutoSize, WithEvents, WithColumnFormatting, WithStyles {

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $workSheet = $event->sheet->getDelegate();
            },
        ];
    }

    public function styles(Worksheet $sheet){}

    public function columnWidths(): array
    {
        return [];
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

        $payments = RealEstatePayment::whereNull('reservation_number')->orWhere('reservation_number', '=', '')
            ->with('paymentStatuses')
            ->get();

        $data = [];
        $headers = [
            'Transaction ID', 'Client #', 'Reservation #', 'Type', 'First Name', 'Middle Name', 'Last Name',
            'Payment Gateway', 'Amount', 'Paid At', 'Status', 'Remarks', 'Payment Gateway Reference #',
        ];
        if( $payments->count() > 0 ) {
            foreach( $payments as $payment ) {
                $data[] = [
                    'transaction_id' => $payment->transaction_id,
                    'client_number' => $payment->client_number,
                    'reservation_number' => $payment->reservation_number,
                    'payment_type' => $payment->payment_type,
                    'first_name' => $payment->first_name,
                    'middle_name' => $payment->middle_name,
                    'last_name' => $payment->last_name,
                    'payment_gateway' => $payment->payment_gateway,
                    'amount' => $payment->payment_amount,
                    'paid_at' => date('d M Y', strtotime($payment->paid_at)),
                    'status' => isset($payment->paymentStatuses[0]) ? $payment->paymentStatuses[0]->status : '',
                    'remarks' => $payment->remarks,
                    'payment_gateway_refeference_number' => $payment->payment_gateway_reference_number,
                ];
            }
            
        }

        return view('exports.SalesAdminPortal.unidentified_report', ['headers' => $headers, 'data' => $data]);
    }
}