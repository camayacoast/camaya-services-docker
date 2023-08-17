<?php

namespace App\Http\Controllers\AutoGate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AutoGate\Pass;
use Carbon\Carbon;

class AquaFunWaterParkAccess extends Controller
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

        $passes = Pass::where('usable_at', '<=', $date)
                    ->where('expires_at', '>', $date)
                    ->whereNull('deleted_at')
                    ->where('type', 'Aqua Fun Water Park Access')
                    ->where('mode', 'access')
                    ->whereHas('booking', function ($q) {
                        $q->where('status', '!=', 'cancelled');
                    })
                    ->with(['booking' => function ($q) {
                        $q->select('reference_number', 'status');
                    }])
                    ->with('guest')
                    ->get();

        $array = [
            'available' => 0,
            'adult' => 0,
            'kid' => 0,
            'infant' => 0,
            'used' => 0
        ];

        foreach ($passes as $pass) {
            if ($pass['count'] >= 1) {
                $array['available']++;
                if ($pass['guest']['type'] == 'adult') {
                    $array['adult']++;
                } else if ($pass['guest']['type'] == 'kid') {
                    $array['kid']++;
                } else if ($pass['guest']['type'] == 'infant') {
                    $array['infant']++;
                }
            } else if ($pass['status'] == 'consumed') {
                $array['used']++;
            }
        }

        return $array;
    }
}
