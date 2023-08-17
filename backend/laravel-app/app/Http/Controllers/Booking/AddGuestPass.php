<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AutoGate\Pass;
use App\Models\Booking\Guest;
use App\Models\Booking\Stub;

use Carbon\Carbon;

class AddGuestPass extends Controller
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

        $guest = Guest::where('reference_number', $request->guest_reference_number)->with('booking')->first();

        $stub_details = Stub::where('id', $request->stub)->first();

        $usable_format = $request->starttime ?  Carbon::parse($request->starttime)->setTimezone('Asia/Manila')->isoFormat('HH:mm:ss') : $stub_details['starttime'];
        $expires_format = $request->endtime ?  Carbon::parse($request->endtime)->setTimezone('Asia/Manila')->isoFormat('HH:mm:ss') : $stub_details['endtime'];

        // return response()->json([$arrival_date = Carbon::parse($guest->booking->start_datetime)->format('Y-m-d')." ".$usable_format], 400);

        $arrival_date = Carbon::parse($guest->booking->start_datetime)->format('Y-m-d');
        $departure_date = Carbon::parse($guest->booking->end_datetime)->format('Y-m-d');

        $newPass = Pass::create([
            'booking_reference_number' => $guest->booking_reference_number,
            'guest_reference_number' => $guest->reference_number,
            // 'card_number' => $card_number,
            // 'inclusion_id' => null, 
            'pass_code' => Pass::generate(),
            'category' => $stub_details['category'],
            'count' => $request->count,
            'interfaces' => $stub_details['interfaces'],
            'mode' => $stub_details['mode'],
            'type' => $stub_details['type'],
            'status' => 'created',
            'usable_at' => $arrival_date." ".$usable_format,
            'expires_at' => $departure_date." ".$expires_format
        ]);

        if (!$newPass) {
            return response()->json(['error' => 'PASS_NOT_SAVED', 'message' => 'Pass did not saved'], 400);
        }

        return response()->json($newPass, 200);
    }
}
