<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\LotInventory;
use App\Models\RealEstate\RealEstatePayment;

class UpdateReservationClientNumber extends Controller
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
        // if ($request->user()->user_type != 'admin') return false;
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.UpdateClientNumber.Reservation')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $reservation = Reservation::where('id', $request->id)->first();

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        $exist = Reservation::where('client_number', $request->client_number)->first();

        if ($exist) {
            return response()->json(['message' => 'Client number is taken.'], 400);
        }

        $reservation_current_client_number = $reservation->client_number;

        if( is_null($reservation_current_client_number) || $reservation_current_client_number == '' ) {
            Reservation::where('id', $request->id)->update(['recalculated' => 0]);
        }

        // Update reservation details
        $excluded_types = ['hoa_fees', 'others'];
        $client_number_payments = RealEstatePayment::where('client_number', $request->client_number)
            ->whereNotIn('payment_type', $excluded_types)
            ->whereNotNull('payment_type')
            ->where('payment_type', '!=', '')
            ->get();
            
        $client_number_payments_count = $client_number_payments->count();

        $reservation_payments = RealEstatePayment::where('reservation_number', $reservation->reservation_number)
            ->whereNotIn('payment_type', $excluded_types)
            ->whereNotNull('payment_type')
            ->where('payment_type', '!=', '')
            ->get();

        $reservation_payments_count = $reservation_payments->count();

        $reservation_update_data = ['client_number' => $request->client_number];

        if( $client_number_payments_count <= 0 && $reservation_payments_count <= 0 ) {
            $reservation_update_data['recalculated'] = 1;
        }

        Reservation::where('id', $request->id)->update($reservation_update_data);

        // Update client number
        LotInventory::where('subdivision', $reservation->subdivision)
                    ->where('block', $reservation->block)
                    ->where('lot', $reservation->lot)
                    ->update([
                        'client_number' => $request->client_number,
                    ]);
        
        // update payments
        RealEstatePayment::where('reservation_number', $reservation->reservation_number)->update([
            'client_number' => $request->client_number,
        ]);

        $reservation->refresh();

        return $reservation->load('client.information')
                        ->load('agent.team_member_of.team')
                        ->load('co_buyers.details')
                        ->load('promos')
                        ->load('attachments')
                        ->load('referrer')
                        ->load('referrer_property_details')
                        ->load('amortization_schedule.penalties')
                        ->load('sales_manager')
                        ->load('sales_director')
                        ->load('amortization_fees.added_by');

    }
}
