<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\ReservationPaymentImport;
use App\Imports\ReservationConfirmationPaymentImport;
use App\Models\RealEstate\RealEstatePayment;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class ImportPaymentData extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' || 
                !$request->user()->hasPermissionTo('RealEstate.BulkUpload.Payments') || 
                !$request->user()->hasPermissionTo('SalesAdminPortal.AddPayment.AmortizationLedger')
            ) {
                if( !$request->user()->hasPermissionTo('RealEstate.BulkUpload.Payments') ) {
                    return response()->json(['message' => 'Unauthorize: RealEstate.BulkUpload.Payments'], 400);
                } else if( !$request->user()->hasPermissionTo('SalesAdminPortal.AddPayment.AmortizationLedger') ) {
                    return response()->json(['message' => 'Unauthorized: SalesAdminPortal.AddPayment.AmortizationLedger'], 400);
                } else {
                    return response()->json(['message' => 'Unauthorized.'], 400);
                }
                
            }
        }

        // Direct upload
        // $import = new ReservationPaymentImport;

        // upload with confirmation | under development
        $import = new ReservationConfirmationPaymentImport;

        $import->user = $request->user();

        Excel::import($import, request()->file('bulkPayment'));
        return $import->reports;
    }

    public function bulk_upload_payments(Request $request)
    {
        if( isset($request->rows) ) {
            $rows = [];
            $collections = [];
            $data = $request->rows['data'];

            // Add 0 index to collection to bypass the labeling in excel file
            $collections[] = new Collection([]);

            foreach( $data as $key => $records ) {
                $collections[] = new Collection($records);
            }

            $rows = new Collection($collections);
            $import = new ReservationPaymentImport;
            $import->record_type = 'bulk_upload';
            $import->user = $request->user();
            $import->collection($rows);

            return $import->reports;
        }
    }

    public function payment_dashboard_report(Request $request)
    {
        switch ($request->type) {
            case 'unidentified_collection_report':
                $UnidentifiedReport = new \App\Exports\SalesAdminPortal\UnidentifiedReport();
                return Excel::download(
                    $UnidentifiedReport,
                    'unidentified_collection_report.xlsx'
                ); 
                break;
            default:
                break;
        }
    }

    public function generate_report(Request $request)
    {
        $PaymentimportReport = new \App\Exports\SalesAdminPortal\PaymentImportReport();
        $PaymentimportReport->reports = $request->data;
        return Excel::download(
            $PaymentimportReport,
            'report.xlsx'
        ); 
    }
}
