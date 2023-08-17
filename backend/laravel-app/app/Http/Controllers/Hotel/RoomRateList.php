<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomRate;

class RoomRateList extends Controller
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
        return RoomRate::with(['room_type' => function ($q) {
            $q->with('images');
            $q->with('property:id,code,name');
        }])
        ->orderBy('created_at', 'desc')
        ->get();
    }
}
