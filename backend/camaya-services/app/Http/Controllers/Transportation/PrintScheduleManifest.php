<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Schedule;
use App\Models\Transportation\Trip;

use App\Exports\TransportationManifest;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as BaseExcel;
use Illuminate\Support\Facades\Storage;

use PDF;

class PrintScheduleManifest extends Controller
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
        // return $request->all();

        // $schedule = Schedule::where('trip_number', $this->trip_number)
        //                     ->with('route.origin')
        //                     ->with('route.destination')
        //                     ->with('transportation')
        //                     ->first();

        // $trip_bookings = Trip::where('trip_number', $request->trip_number)
        //             ->with([
        //                 'passenger' => function ($q) {
        //                     //
        //                 }
        //             ])
        //             ->with(['booking' => function ($q) {
        //                 $q->with('customer');
        //             }])
        //             ->whereIn('status', $request->status)
        //             ->get();

        $file_name = date('Y-m-d H:i:s').' manifest ('.implode(', ', $request->status).') '.$request->trip_number.'.xlsx';
        // $file_name = date('YmdHis').'manifest'.$request->trip_number.'.xlsx';
        // return Excel::download(new InvoicesExport, 'invoices.xlsx');
        // Log::debug(env('APP_URL').Storage::url("manifest/".$file_name));
        $file = Excel::store(new TransportationManifest($request->trip_number, $request->status), "/manifest/".$file_name, 'public');
        // $file = Excel::store(new TransportationManifest($request->trip_number, $request->status), "/manifest/".$file_name);
        
        return (env('APP_URL').Storage::url("manifest/".$file_name));
    }
}
