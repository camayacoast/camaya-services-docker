<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\GeneratedVoucher;
use App\Models\Booking\ActivityLog;

use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class AutoCancelVoucher extends Controller
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

        /**
         * Checks if booking is already for cancellation
         */

        $vouchersToCancel = GeneratedVoucher::whereIn('payment_status', ['unpaid'])
                ->whereIn('voucher_status', ['new'])
                ->whereNull('paid_at')
                ->whereNull('used_at')
                ->whereNull('cancelled_at')
                ->where('created_at', '<=', Carbon::now()->sub(48, 'hours')->setTimezone('Asia/Manila'))
                ->select('transaction_reference_number')
                ->get();

        // Execute cancel
        if ($vouchersToCancel) {

            $vouchersToCancelReferenceNumbers = collect($vouchersToCancel)->pluck('transaction_reference_number')->all();

            GeneratedVoucher::whereIn('transaction_reference_number', $vouchersToCancelReferenceNumbers)
                    ->whereNull('cancelled_at')
                    ->update([
                        'voucher_status' => 'cancelled',
                        'cancelled_at' => Carbon::now()->setTimezone('Asia/Manila'),
                    ]);

            Log::info('Auto-cancelled vouches: '. implode(', ', $vouchersToCancelReferenceNumbers));
        }

        // Create log
        // ActivityLog::create([
        //     'booking_reference_number' => $booking->reference_number,

        //     'action' => 'auto_cancel',
        //     'description' => 'System has auto cancelled the booking.',
        //     'model' => 'App\Models\Booking\Booking',
        //     'model_id' => $booking->id,
        //     'properties' => null,

        //     'created_by' => null,
        // ]);

        // Logs cancelled bookings
        
        
        return $vouchersToCancelReferenceNumbers;
    }
}
