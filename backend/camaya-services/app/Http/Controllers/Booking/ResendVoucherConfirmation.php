<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Voucher;
use App\Models\Booking\GeneratedVoucher;
use App\Models\Booking\Customer;
use App\Models\Booking\ActivityLog;
use Carbon\Carbon;

use App\Mail\Booking\VoucherConfirmation;
use App\Mail\Booking\VoucherPending;

use Illuminate\Support\Facades\Mail;

class ResendVoucherConfirmation extends Controller
{
    /**
     * Handle the incoming request.
     *  q   
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // return $request->all();

        $paid_vouchers = GeneratedVoucher::with('voucher')->with('customer')
                    ->where('payment_status', 'paid')
                    ->whereNotIn('voucher_status', ['voided', 'cancelled'])
                    ->where('transaction_reference_number', $request->transaction_reference_number)
                    ->get();

        $unpaid_vouchers = GeneratedVoucher::with('voucher')->with('customer')
                    ->where('payment_status', 'unpaid')
                    ->whereNotIn('voucher_status', ['voided', 'cancelled'])
                    ->where('transaction_reference_number', $request->transaction_reference_number)
                    ->get();

        if (count($unpaid_vouchers) > 0 ) {
            $mail = Mail::to($unpaid_vouchers[0]->customer->email)
                         ->send(new VoucherPending($request->transaction_reference_number));
        } else if (count($paid_vouchers) > 0 ) { 
            $mail = Mail::to($paid_vouchers[0]->customer->email)
                ->send(new VoucherConfirmation($paid_vouchers, $request->transaction_reference_number));
        } else {
            return response()->json(['status' => 'Error', 'message'=>'No paid/unpaid voucher'], 400);
        }

        

        // if (!$mail) {
        //     return response()->json(['error' => 'FAILED_TO_SEND_EMAIL', 'message'=>'Failed to send voucher confirmation.'], 400);
        // }

        return response()->json(['status' => 'OK', 'message'=>'Voucher email sent!'], 200);

    } 
}
