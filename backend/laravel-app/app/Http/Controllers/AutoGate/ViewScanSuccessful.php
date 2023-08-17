<?php

namespace App\Http\Controllers\AutoGate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;
use App\Models\AutoGate\Pass;

class ViewScanSuccessful extends Controller
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

        $pass = Pass::find($request->pass_id);

        if (!isset($pass)) {
            return 'ACCESS PASS NOT FOUND';
        }

        $guest = Guest::where('booking_reference_number', $pass->booking_reference_number)->with('booking')->first();

        return view('autogate.scan-successful', [
            'pass_id' => $request->pass_id,
            'pass' => $pass,
            'guest' => $guest
        ]);
    }
}
