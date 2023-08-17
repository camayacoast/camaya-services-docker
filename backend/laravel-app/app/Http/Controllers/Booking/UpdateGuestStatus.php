<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Guest;

class UpdateGuestStatus extends Controller
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


        $guest = Guest::find($request->guest_id);

        if (!$guest) {
            return response()->json(['error' => 'GUEST_NOT_FOUND'], 400);
        }

        Guest::where('id', $request->guest_id)
            ->update([
                'status' => $request->guest_status
            ]);

        return $guest->refresh();
    }
}
