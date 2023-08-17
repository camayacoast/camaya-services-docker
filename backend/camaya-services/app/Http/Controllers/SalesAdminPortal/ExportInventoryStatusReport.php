<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;

class ExportInventoryStatusReport extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Lot')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $statusReport = new \App\Exports\SalesAdminPortal\InventoryStatusReport();
        $statusReport->property_type = $request->property_type;

        return Excel::download(
                $statusReport, 
                'report.xlsx'
            ); 
    }

    public function inventory_template(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Lot')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $InventoryTemplate = new \App\Exports\SalesAdminPortal\InventoryTemplate();
        $InventoryTemplate->property_type = $request->property_type;

        return Excel::download(
                $InventoryTemplate, 
                'report.xlsx'
            ); 
    }
}
