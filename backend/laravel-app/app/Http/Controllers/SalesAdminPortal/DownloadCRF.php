<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Barryvdh\DomPDF\Facade as PDF;

use App\Models\RealEstate\Client;

class DownloadCRF extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.DownloadCRF.Client')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $client = Client::where('id', $request->client_number)
                ->with('information')
                ->with('agent.agent_details')
                ->first();

        $pdf = PDF::loadView('pdf.sales_admin_portal.crf', $client);
        
        return $pdf->download('crf.pdf');
    }
}
