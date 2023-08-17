<?php

namespace App\Http\Controllers\AutoGate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FTTGuestsExit extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $date = $request->date ?? now()->setTimezone('Asia/Manila')->format('Y-m-d h:i:s');
       
        return $passes = \App\Models\AutoGate\Pass::where('usable_at', '<=', $date)
                    ->where('expires_at', '>', $date)
                    ->whereNull('deleted_at')
                    // ->whereNotIn('status', ['consumed', 'voided'])
                    ->where('type', 'FTT Pass Exit')
                    ->where('mode', 'access')
                    ->whereHas('booking', function ($q) {
                        $q->where('status', '!=', 'cancelled');
                    })
                    ->with(['booking' => function ($q) {
                        $q->with('customer');
                        $q->select('reference_number', 'status', 'customer_id');
                    }])
                    ->with('guest')
                    ->get();
    }
}
