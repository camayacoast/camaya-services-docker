<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SalesAdminPortal\Payment;
use Illuminate\Http\Request;

use App\Models\RealEstate\AmortizationSchedule;
use App\Models\RealEstate\AmortizationPenalty;
use App\Models\RealEstate\CashTermPenalty;

class AddPenalty extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.AddPenalty.AmortizationLedger')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        if( $request->payment_terms_type == 'cash' ) {
            CashTermPenalty::create([
                'reservation_number' => $request->reservation_number,
                'cash_term_ledger_id' => $request->id,
                'number' => $request->number,
                'is_old' => 0,
                'penalty_amount' => $request->penalty_amount,
                'type' => 'amortization_penalty',
            ]);
        } else {
            AmortizationPenalty::create([
                'reservation_number' => $request->reservation_number,
                'amortization_schedule_id' => $request->id,
                'number' => $request->number,
                'is_old' => 0,
                'penalty_amount' => $request->penalty_amount,
                'type' => 'amortization_penalty',
            ]);
        }
    }

    public function waive_penalty(Request $request)
    {

        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' || 
                !$request->user()->hasPermissionTo('RealEstate.Void.Penalty') ) 
            {
                return response()->json(['message' => 'Unauthorized: please add RealEstate.Void.Penalty permission on your account.'], 400);
            }
        }

        $penalty_status = $request->penalty_status;
        $status = $request->status;
        $penalty_payment_count = $request->penalty_payment_count;

        AmortizationPenalty::where('reservation_number', $request->reservation_number)
            ->where('number', $request->number)
            ->update([
                'status' => $status
            ]);

        if( ( $penalty_status !== null && $status !== 'waived' && $penalty_payment_count > 0 ) || 
            ( $penalty_status === 'waived_wp' && $status !== null && $penalty_payment_count > 0 ) || 
            ( $penalty_status === 'waived' && $status === null && $penalty_payment_count > 0 ) || 
            ( $penalty_status === null && $status === 'waived_wp' && $penalty_payment_count > 0 )
        ){
            $payment = new Payment;
            $request->waive = ($request->status == 'waived' || $request->status == 'waived_wp') ? true : false;
            $request->penalty_number = $request->number;
            $request->collection_recalculate = true;
            $payment->waive_penalty = true;
            $payment->recompute_account($request);
        }
        
        return true;
    }
}
