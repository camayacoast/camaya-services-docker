<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Setting;

class UpdateFerryPassengersLimit extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $value = $request->value;

        if (!is_int($value)) {
            return response()->json(['error' => 'ERROR_NOT_INT'], 400);
        }

        if ($value < 0) {
            return response()->json(['error' => 'ERROR_LESS_THAN_ZERO_NOT_ALLOWED'], 400);
        }

        $FerryPassengerEdit = Setting::where('code', $request->type)->first();

        if (!$FerryPassengerEdit) {
            return response()->json(['error' => 'ERROR'], 400);
        }

        $FerryPassengerEdit->update([
            'value' => $value,
        ]);

        return $FerryPassengerEdit->refresh();
    }
}
