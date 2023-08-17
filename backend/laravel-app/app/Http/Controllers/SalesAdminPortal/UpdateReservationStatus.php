<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\Reservation;
use App\Models\RealEstate\LotInventory;

class UpdateReservationStatus extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.ModifyStatus.Reservation')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $reservation = Reservation::where('id', $request->id)->first();

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        Reservation::where('id', $request->id)->update([
            'status' => $request->status,
        ]);

        if ($request->status == 'cancelled' || $request->status == 'void') {

            // Remove client number
            // Return status to available
            LotInventory::where('subdivision', $reservation->subdivision)
                        ->where('block', $reservation->block)
                        ->where('lot', $reservation->lot)
                        ->update([
                            'status' => 'available',
                            'status2' => 'Available',
                            'client_number' => null,
                        ]);
                        
        } else {
            LotInventory::where('subdivision', $reservation->subdivision)
                        ->where('block', $reservation->block)
                        ->where('lot', $reservation->lot)
                        ->update([
                            'status' => 'reserved',
                            'status2' => 'Reservation',
                            'client_number' => $reservation->client_number,
                        ]);
        }

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
