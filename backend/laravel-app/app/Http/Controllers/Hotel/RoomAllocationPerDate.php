<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

use App\Models\Hotel\RoomAllocation;

use DB;

class RoomAllocationPerDate extends Controller
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

        $room_allocations = RoomAllocation::where('status', 'approved')
                    ->whereDate('date', '>=', $request->start_date)
                    ->whereDate('date', '<=', $request->end_date)
                    ->select(
                        'entity',
                        'allocation',
                        DB::raw("DATE_FORMAT(date, '%Y-%m-%d') as date"),
                        'status',
                        // 'id',
                        'used',
                        'room_type_id'
                    )
                    ->with('room_type:id,name')
                    ->get();

        return collect($room_allocations)->groupBy('date')->all();
    }
}
