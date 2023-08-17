<?php

namespace App\Http\Controllers\GolfMembership;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\PaymentTransaction;

class UpdatePaymentRecord extends Controller
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

        $payment_transaction = PaymentTransaction::find($request->id);
        
        if (!$payment_transaction) {
            return response()->json(['message' => 'Payment transaction does not exist.'], 404);
        }

        PaymentTransaction::where('id', $request->id)->update([
            $request->type => $request->value
        ]);

        return 'OK';

    }
}
