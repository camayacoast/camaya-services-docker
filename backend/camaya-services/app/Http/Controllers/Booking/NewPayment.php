<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Invoice;
use App\Models\Booking\Payment;
use App\Models\Booking\ActivityLog;

use App\Models\Booking\GeneratedVoucher;

use Carbon\Carbon;

use DB;

class NewPayment extends Controller
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

        $voucher = GeneratedVoucher::where('voucher_code', $request->voucher)->first();

        if (!$voucher && $request->mode_of_payment == 'voucher') {
            return response()->json(['message' => 'Voucher not found'], 404);
        } 
        
        if ($voucher && $request->mode_of_payment == 'voucher') {

            if ($voucher->voucher_status == 'redeemed') {
                return response()->json(['message' => 'Voucher already redeemed.'], 400);
            }

            if ($voucher->voucher_status == 'voided') {
                return response()->json(['message' => 'Voucher is voided.'], 400);
            }

            if ($voucher->voucher_status == 'expired') {
                return response()->json(['message' => 'Voucher already expired.'], 400);
            }
            
            if ($voucher->paid_at == null) {
                return response()->json(['message' => 'Voucher not yet paid.'], 400);
            }

            if ($voucher->payment_status == 'unpaid') {
                return response()->json(['message' => 'Voucher not yet paid.'], 400);
            }

        }

        ///////// BEGIN TRANSACTION //////////
        $connection = DB::connection('camaya_booking_db');
        $connection->beginTransaction();

        $mode_of_payment = $request->mode_of_payment;
        $amount = $request->amount;

        if ($mode_of_payment == 'voucher') {
            $amount = $voucher->price;
        }
        

        $invoice = Invoice::where('id', $request->invoice_id)->first();
        $balance = (($invoice->balance - $amount) <= 0.00) ? 0 : $invoice->balance - $amount;
        $change = (($amount - $invoice->balance) > 0.00) ? ($amount - $invoice->balance) : 0;

        if ($invoice->balance <= 0) {
            return response()->json([
                'error' => 'PAYMENT_DENIED',
                'message' => "Your payment was unsuccessful. This invoice is already fully paid.",
            ], 400);
        }

        if ($mode_of_payment == 'voucher') {

            // Update voucher
            GeneratedVoucher::where('voucher_code', $request->voucher)
            ->update([
               'voucher_status' => 'redeemed',
               'used_at' => Carbon::now(),
               'booking_reference_number' => $invoice->booking_reference_number,
            ]);
        }

        $provider = null;

        if (in_array($request->mode_of_payment, ['online_payment_paypal', 'online_payment_dragonpay', 'online_payment_paymaya'])) {
            $mode_of_payment = "online_payment";
            $provider = explode("_", $request->mode_of_payment)[2] ?? "none";
        }

        /**
         * Generate New Unique Payment Reference Number
         */ 
        $newPaymentReferenceNumber = "P-".\Str::upper(\Str::random(6));

        // Creates a new reference number if it encounters duplicate
        while (Payment::where('payment_reference_number', $newPaymentReferenceNumber)->exists()) {
            $newPaymentReferenceNumber = "P-".\Str::upper(\Str::random(6));
        }

        $newPayment = $invoice->payments()->create([
            'booking_reference_number' => $invoice->booking_reference_number,
            'payment_reference_number' => $newPaymentReferenceNumber,
            'amount' => $amount,
            'voucher_id' => $mode_of_payment == 'voucher' ? $voucher->id : null,
            'mode_of_payment' => $mode_of_payment,
            'provider' => $provider ?? "none",
            'remarks' => $request->remarks,
            'paid_at' => Carbon::now(),
            'status' => 'confirmed',
            'created_by' => $request->user()->id,
        ]);

        // Update invoice
        $updateInvoice = Invoice::where('id', $invoice->id)
            ->update([
                'total_payment' => $invoice->total_payment + $amount,
                'balance' => $balance,
                // 'change' => $invoice->change + $change,
                'status' => ($balance <= 0.00) ? 'paid' : 'partial',
                'paid_at' => ($balance <= 0.00) ? Carbon::now() : null,
            ]);

        if (!$newPayment || !$updateInvoice) {

            $connection->rollBack();

            return response()->json([
                'error' => 'PAYMENT_ERROR',
                'message' => "Your payment was unsuccessful. Try again.",
            ], 400);
        }

        $invoice->refresh();

        $connection->commit();

        // Create log
        ActivityLog::create([
            'booking_reference_number' => $invoice->booking_reference_number,

            'action' => 'new_payment',
            'description' => $request->user()->first_name.' '.$request->user()->last_name.' has made a new payment '.$provider.' - '.$mode_of_payment.' with the amount of P'.$amount.'.',
            'model' => 'App\Models\Booking\Invoice',
            'model_id' => $invoice->id,
            'properties' => null,

            'created_by' => $request->user()->id,
        ]);
        // $connection->rollback();

        return response()->json(['payment' => $newPayment, 'invoice' => $invoice], 200);
    }
}
