<?php

namespace App\Http\Controllers\AFParkingMonitoring;

use App\Http\Controllers\Controller;
use App\Models\AutoGate\Tap;
use App\Models\Booking\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Mode extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke($mode = 1)
    {                
        Cache::put('af-parking-monitoring-mode', $mode);

        return [
            'status' => 'success'
        ];
    }
}
