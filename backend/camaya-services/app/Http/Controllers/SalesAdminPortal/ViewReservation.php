<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SalesAdminPortal\Payment;
use Illuminate\Http\Request;

use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\AmortizationSchedule;
use App\Models\RealEstate\CashTermLedger;

class ViewReservation extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Reservation')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $reservation = Reservation::where('reservation_number', $request->reservation_number)->first();

        if (!$reservation) {
            return response()->json(['error' => 'RESERVATION_NOT_FOUND', 'message' => 'Reservation not found.'], 404);
        }

        if( $reservation['recalculated'] === 0 ) {
            $Payment = new Payment;
            $request->collection_recalculate = true;
            $Payment->recompute_account($request);
        }

        $payments = Payment::getPaymentDetails($reservation);
        $reservation['payment_details'] = $payments;

        $reservation['request_user_id'] = $request->user()->id;

        if( $reservation->status !== 'draft' ) {
            $amortization_schedule = AmortizationSchedule::collections($reservation);
            $reservation['amortization_collections'] = $amortization_schedule;
    
            $cash_ledger = CashTermLedger::collections($reservation);
            $reservation['cash_ledger_collections'] = $cash_ledger;
        }

        return $reservation->load('client.information')
                        ->load('agent.team_member_of.team')
                        ->load('co_buyers.details')
                        ->load('promos')
                        ->load('attachments')
                        ->load('referrer')
                        ->load('referrer_property_details')
                        ->load(['amortization_schedule.penalties', 'amortization_schedule.payments'])
                        ->load(['cash_term_ledger.penalties', 'cash_term_ledger.payments'])
                        ->load('sales_manager')
                        ->load('sales_director')
                        ->load('amortization_fees.added_by');
    }

    public function client_reservation(Request $request)
    {
        $property_type = $request->property_type;
        $subdivision = $request->subdivision;
        $block = $request->block;
        $lot = $request->lot;

        $reservation = Reservation::where('property_type', $property_type)
            ->where('subdivision', $subdivision)
            ->where('block', $block)
            ->where('lot', $lot)
            ->whereNotIn('status', ['draft', 'cancelled', 'void'])
            ->with('client.information')
            ->first();

        if( $reservation ) {
            return $reservation;
        } else {
            return 0;
        }
    }
}
