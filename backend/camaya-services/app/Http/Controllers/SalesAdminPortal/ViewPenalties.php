<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\AmortizationPenalty;
use App\Models\RealEstate\CashTermPenalty;

class ViewPenalties extends Controller
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

        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.ViewPenalties.AmortizationLedger')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        if( $request->payment_terms_type === 'cash' ) {
            return CashTermPenalty::where('reservation_number', $request->reservation_number)
                    ->orderBy('number', 'asc')
                    ->with('cash_term_ledger:id,due_date,amount,number,transaction_id')
                    ->with('payments')
                    ->get();
        } else {
            return AmortizationPenalty::where('reservation_number', $request->reservation_number)
                    ->orderBy('number', 'asc')
                    ->with('amortization_schedule:id,due_date,amount,is_collection,number,transaction_id')
                    ->with('payments')
                    ->get();
        }

        
    }
}
