<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Inclusion;
use App\Models\Booking\Invoice;
use App\Models\Booking\Booking;

class UpdateInclusionDiscount extends Controller
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

        $inclusion = Inclusion::where('id', $request->id)->first();

        if (!$inclusion) {
            return response()->json(['error' => 'INCLUSION_NOT_FOUND'], 400);
        }

        if ($request->amount > ($inclusion->price * $inclusion->quantity)) {
            return response()->json(['error' => 'AMOUNT_CAN_NOT_GO_HIGHER_THAN_INCLUSION_PRICE'], 400);
        }

        $inclusion->update([
            'discount' => $request->amount,
        ]);

        // Update invoice
        $invoice = Invoice::where('id', $inclusion->invoice_id)->first();

        $inclusions = Inclusion::where('invoice_id', $invoice->id)
                                    ->whereNull('deleted_at')
                                    ->get();

        $total_inclusions_cost = 0;
        foreach ($inclusions as $i) {
            $total_inclusions_cost = $total_inclusions_cost + (($i['price'] * $i['quantity']) - $i['discount']);
        }

        $grand_total = ($total_inclusions_cost - $invoice->discount);
        $balance = ($total_inclusions_cost - $invoice->discount) - $invoice->total_payment;

        $invoice->update([
            'total_cost' => $total_inclusions_cost,
            'grand_total' => $grand_total < 0 ? 0 : $grand_total,
            'balance' => $balance < 0 ? 0 : $balance,
        ]);

        $booking = Booking::where('reference_number', $inclusion->booking_reference_number)
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
