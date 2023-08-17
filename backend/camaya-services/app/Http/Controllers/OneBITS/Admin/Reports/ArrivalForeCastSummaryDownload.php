<?php

namespace App\Http\Controllers\OneBITS\Admin\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OneBITS\ArrivalForeCastSummaryExport;


class ArrivalForeCastSummaryDownload extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        return Excel::download(
            new ArrivalForeCastSummaryExport($request->start_date, $request->end_date), 
            'report.xlsx'
        );        
    }
}
