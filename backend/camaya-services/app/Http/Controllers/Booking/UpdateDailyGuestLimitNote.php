<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\DailyGuestLimitNote;

class UpdateDailyGuestLimitNote extends Controller
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
        if (!$request->date) {
            return false;
        }

        DailyGuestLimitNote::updateOrCreate([
            'date' => $request->date. ' 00:00:00'
        ],[
            'note' => $request->remarks,
            'updated_by' => $request->user()->id,
            // 'created_at' => now(),
        ]);

        return response()->json([], 200);
    }
}
