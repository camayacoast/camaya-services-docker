<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Seat;

class CreateSeat extends Controller
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

        $exists = Seat::where('transportation_id', $request->transportation_id)->where('number', $request->number)->exists();
        
        if ($exists) {
            return response()->json(['error' => 'Seat number already exist for this transportation.'], 400);
        }

        $newSeat = Seat::create([
            'transportation_id' => $request->transportation_id,
            'number' => $request->number,
            'type' => $request->type,
            'status' => $request->status,
            'auto_check_in_status' => $request->auto_check_in_status,
            'order' => $request->order,
        ]);

        if (!$newSeat->save()) {
            return response()->json(['error' => 'Could not save seat.'], 400);
        }

        return response()->json($newSeat, 200);
    }
}
