<?php

namespace App\Http\Controllers\AutoGate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\Models\Booking\Guest;
use App\Models\Booking\Invoice;

use App\Models\AutoGate\Pass;
use Carbon\Carbon;

class DeletePass extends Controller
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

        $pass = Pass::find($request->pass_id);

        if (!$pass) {
            return response()->json(['error' => 'PASS_NOT_FOUND'], 400);
        }

        $pass->update([
            'deleted_at' => Carbon::now(),
            'deleted_by' => $request->user()->id,
        ]);

        $guest = Guest::where('reference_number', $pass->guest_reference_number)
                            ->with('guestInclusions')
                            ->with('passes.trip')
                            ->with('guestTags')
                            ->first();

        return $guest;
    }
}
