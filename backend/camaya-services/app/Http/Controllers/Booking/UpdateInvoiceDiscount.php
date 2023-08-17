<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Invoice;
use App\Models\Booking\Booking;

class UpdateInvoiceDiscount extends Controller
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

        $invoice = Invoice::where('id', $request->id)->first();

        $balance = ($invoice->total_cost - $request->amount) - $invoice->total_payment;
        $grand_total = $invoice->total_cost - $request->amount;

        if ($request->amount > $invoice->total_cost) {
            return response()->json(['error' => 'AMOUNT_CAN_NOT_GO_HIGHER_THAN_INVOICE_TOTAL'], 400);
        }

        $invoice->update([
            'discount' => $request->amount,
            'grand_total' => $grand_total < 0 ? 0 : $grand_total,
            'balance' => $balance < 0 ? 0 : $balance,
        ]);

        $booking = Booking::where('reference_number', $invoice->booking_reference_number)
                            ->with(['inclusions' => function ($query) {
                                $query->with('guestInclusion');
                                $query->with('packageInclusions.guestInclusion');
                                $query->with('deleted_by_user');
                                $query->withTrashed();
                            }])
                            ->addSelect(['*',
                                'balance' => Invoice::select(\DB::raw('sum(balance) as total_balance'))
                                                    ->whereColumn('booking_reference_number', 'bookings.reference_number')
                                                    ->limit(1)
                            ])
                            ->with(['invoices' => function ($query) {
                                $query->with(['inclusions' => function ($query) {
                                    $query->with('guestInclusion');
                                    $query->with('packageInclusions.guestInclusion');
                                    $query->with('deleted_by_user');
                                    $query->withTrashed();
                                }]);
                                $query->with('payments');
                            }])
                            ->first();

        return $booking;
    }
}
