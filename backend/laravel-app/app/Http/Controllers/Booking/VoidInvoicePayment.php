<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Invoice;
use App\Models\Booking\Payment;
use App\Models\Booking\GeneratedVoucher;

use Carbon\Carbon;

class VoidInvoicePayment extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
        // return $request->all();

        $payment = Payment::where('id', $request->id)->first();

        $invoice = Invoice::where('id', $payment->invoice_id)->first();

        if (!$invoice || !$payment) {
            return response()->json(['error' => 'RECORD_MISSING'], 400);
        }

        $payment->update([
            'status' => 'voided',
            'voided_by' => $request->user()->id,
            'voided_at' => Carbon::now()->setTimezone('Asia/Manila'),
        ]);

        /**
         * If mode of payment is voucher return voucher inventory
         */
        if ($payment->mode_of_payment == 'voucher') {
            // Update voucher
            GeneratedVoucher::where('id', $payment->voucher_id)
            ->update([
               'voucher_status' => 'active',   
               'used_at' => Carbon::now(),            
               'booking_reference_number' => null,
            ]);
        }

        /**
         * Update invoice
         */

        $balance = ($invoice->balance + $payment->amount) > $invoice->grand_total ? $invoice->grand_total : ($invoice->balance + $payment->amount);
        $invoice_payment = $invoice->total_payment - $payment->amount;

        // Update invoice
        Invoice::where('id', $invoice->id)
            ->update([
                'total_payment' => $invoice_payment,
                'balance' => $balance,
                // 'change' => $change,
                'status' => ($balance <= 0.00) ? 'paid' : (($invoice->total_payment - $payment->amount == 0) ? 'sent' : 'partial'),
                'paid_at' => ($balance <= 0.00) ? Carbon::now() : null,
            ]);

        return $invoice;
    }
}
