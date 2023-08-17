<?php

namespace App\Http\Controllers\AutoGate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AutoGate\Pass;
use Carbon\Carbon;
use App\Models\Transportation\Transportation;

class FTTPassEntry extends Controller
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
        // return Pass::take(10)->get();
        // return now()->setTimezone('Asia/Manila')->format('Y-m-d h:i:s');
        $date = $request->date ?? now()->setTimezone('Asia/Manila')->format('Y-m-d h:i:s');

        $passes = Pass::where('usable_at', '<=', $date)
                    ->where('expires_at', '>', $date)
                    ->whereNull('deleted_at')
                    ->where('type', 'FTT Pass Entry')
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
        $array['summary'] = $this->summarize($passes);
        return $array;
    }

    public function summarize($passes){
        $transportationList = Transportation::with('seats')
                    ->withCount('activeSeats')
                    ->where('mode', '=', 'land')
                    ->get();

        $vehicles = [];

        $i = 0;
        foreach ($transportationList as $t) {
            $vehicle = [
                "code" => $t->code,
                "name" => $t->name,
                "count" => 0
            ];
            $vehicles[$i] = $vehicle;
            $i++;
        }

        foreach ($passes as $pass) {
            $code = explode(" ", $pass->description)[0];
            
            for ($i = 0; $i < count($vehicles); $i++) {
                if ($vehicles[$i]['code'] === $code) {
                    $vehicles[$i]['count']++;
                }
            }            
        }

        return $vehicles;
    }
}
