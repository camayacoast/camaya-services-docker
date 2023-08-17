<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;

class ExportBISReport extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Reservation')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        return Excel::download(
                new \App\Exports\SalesBISReport(), 
                'report.xlsx'
            ); 
    }
}
