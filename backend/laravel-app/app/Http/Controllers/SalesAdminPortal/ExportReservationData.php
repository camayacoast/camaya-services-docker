<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;

class ExportReservationData extends Controller
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
                // new \App\Exports\SalesBISReport(), 
                new \App\Exports\SalesAdminPortal\ReservationDataReport(), 
                'report.xlsx'
            ); 
    }

    public function penaltyReports(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Reservation')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $reservationPenaltyExports = new \App\Exports\SalesAdminPortal\ReservationPenaltyReports();
        $reservationPenaltyExports->reservation_number = $request->reservation_number;
        $reservationPenaltyExports->payment_terms_type = $request->payment_terms_type;

        return Excel::download(
            // new \App\Exports\SalesBISReport(), 
            $reservationPenaltyExports, 
            'report.xlsx'
        ); 
    }

    public function amortizationReports(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Reservation')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $reservationAmortizationExports = new \App\Exports\SalesAdminPortal\ReservationAmortizationReports();
        $reservationAmortizationExports->reservation_number = $request->reservation_number;
        $reservationAmortizationExports->payment_terms_type = $request->payment_terms_type;

        return Excel::download(
            // new \App\Exports\SalesBISReport(), 
            $reservationAmortizationExports, 
            'report.xlsx'
        ); 
    }

    public function cashLedgerReports(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Reservation')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $reservationAmortizationExports = new \App\Exports\SalesAdminPortal\ReservationCashLedgerReports();
        $reservationAmortizationExports->reservation_number = $request->reservation_number;
        $reservationAmortizationExports->payment_terms_type = $request->payment_terms_type;

        return Excel::download(
            // new \App\Exports\SalesBISReport(), 
            $reservationAmortizationExports, 
            'report.xlsx'
        ); 
    }

    public function import_template(Request $request)
    {
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.View.Reservation')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $reservationAmortizationExports = new \App\Exports\SalesAdminPortal\ReservationImportTemplate();

        return Excel::download(
            // new \App\Exports\SalesBISReport(), 
            $reservationAmortizationExports, 
            'report.xlsx'
        ); 
    }
}
