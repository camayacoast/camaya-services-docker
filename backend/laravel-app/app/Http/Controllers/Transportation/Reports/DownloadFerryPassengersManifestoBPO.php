<?php

namespace App\Http\Controllers\Transportation\Reports;

use App\Exports\Transportation\FerryPassengersTransportationManifestBPO;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DownloadFerryPassengersManifestoBPO extends Controller
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
            new FerryPassengersTransportationManifestBPO($request->tripNumbers, $request->status), 
            'report.xlsx'
        );        
    }
}
