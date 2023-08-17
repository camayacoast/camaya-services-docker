<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\Reservation;

class DownloadBIS extends Controller
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
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.DownloadBIS.Client')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $data = \App\Models\RealEstate\Reservation::where('reservation_number', $request->reservation_number)
                ->with('referrer')
                ->with('referrer_property_details:reservation_number,subdivision,block,lot')
                ->with('client.information')
                ->with('client.spouse')
                ->with('agent')
                ->first();

        $pdf = \PDF::loadView('pdf.sales_admin_portal.ris', [
                                                                'data' => $data
                                                            ]
                                                        );
        
        return $pdf->download('ris.pdf');
    }
}
