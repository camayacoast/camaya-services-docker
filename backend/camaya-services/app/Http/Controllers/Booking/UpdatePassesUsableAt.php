<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AutoGate\Pass;
use Carbon\Carbon;

class UpdatePassesUsableAt extends Controller
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
        $new_datetime = Carbon::parse($request->date)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s');

        $pass = Pass::find($request->id);

        if (!$pass) {
            return response()->json([], 400);
        }

        $pass->update([
            'usable_at' => $new_datetime,
        ]);

        return $pass;
    }
}
