<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Hotel\CreateRoomRateRequest;
use App\Models\Hotel\RoomRate;
use App\Models\Hotel\RoomType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateRoomRate extends Controller
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

        $new_room_rates_to_save = [];

        $start_date = Carbon::parse(date('Y-m-d', strtotime($request->date_range[0])))->setTimezone('Asia/Manila');
        $end_date = Carbon::parse(date('Y-m-d', strtotime($request->date_range[1])))->setTimezone('Asia/Manila');

        // $exclude_days = [];
        
        // if ($request->exclude_days) {
        //     foreach ($request->exclude_days as $days) {
        //         $exclude_days[] = date('Y-m-d', strtotime($days));
        //     }
        // }
        
        // foreach ($request->room_types as $room_type_id) {
        //     $new_room_rates_to_save[] = [
        //         'room_type_id' => $room_type_id,
        //         'start_datetime' => $start_date,
        //         'end_datetime' => $end_date,
        //         'room_rate' => $request->room_rate,
        //         'days_interval' => json_encode($request->allowed_days),
        //         'exclude_days' => json_encode($exclude_days),
        //         'description' => $request->description,
        //         'created_by' => $request->user()->id,
        //         'created_at' => Carbon::now()
        //     ];
        // }

        $roomToUpdate = RoomRate::find($request->id);
        $update = $roomToUpdate->update([
            'start_datetime' => $start_date,
            'end_datetime' => $end_date,
            'room_rate' => $request->room_rate,
            'description' => $request->description,
        ]);                 


        if (!$update) {
            return response()->json(['error' => 'ROOM_RATE_SAVE_FAIL', 'message' => 'Failed to save room rates'], 400);
        }

        $roomToUpdate->refresh();

        return response()->json($roomToUpdate, 200);
    }
}
