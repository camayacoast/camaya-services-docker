<?php

namespace App\Http\Controllers\GolfMembership;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\PaymentTransaction;

class SavePaymentTransactionSource extends Controller
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
        $payment_transaction = PaymentTransaction::find($request->id);
        
        if (!$payment_transaction) {
            return response()->json(['message' => 'Payment transaction does not exist.'], 404);
        }

        PaymentTransaction::where('id', $request->id)->update([
            'source' => $request->value
        ]);

        return 'OK';
    }
}
