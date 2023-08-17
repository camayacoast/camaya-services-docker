<?php

namespace App\Http\Controllers\OneBITS\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Trip;
use App\Models\OneBITS\Ticket;
use App\Models\Transportation\Schedule;
use App\Models\Transportation\Passenger;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
class DownloadBoardingPass extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $tickets = Ticket::where('group_reference_number', $request->group_reference_number)->get();
        $boarding_pass = PDF::loadView('pdf.onebits.boarding_pass', ['tickets' => $tickets]);

        return $boarding_pass->download($request->group_reference_number.'-boarding-pass.pdf');
    }
}
