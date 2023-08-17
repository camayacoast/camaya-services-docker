<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Hotel\CreateRoomRateRequest;
use App\Models\Hotel\RoomRate;
use App\Models\Hotel\RoomType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateRoomRate extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreateRoomRateRequest $request)
    {
        //
        // return $request->all();

        $new_room_rates_to_save = [];

        $start_date = Carbon::parse(date('Y-m-d', strtotime($request->date_range[0])))->setTimezone('Asia/Manila');
        $end_date = Carbon::parse(date('Y-m-d', strtotime($request->date_range[1])))->setTimezone('Asia/Manila');

        $exclude_days = [];
        
        if ($request->exclude_days) {
            foreach ($request->exclude_days as $days) {
                $exclude_days[] = date('Y-m-d', strtotime($days));
            }
        }
        
        foreach ($request->room_types as $room_type_id) {
            $new_room_rates_to_save[] = [
                'room_type_id' => $room_type_id,
                'start_datetime' => $start_date,
                'end_datetime' => $end_date,
                'room_rate' => $request->room_rate,
                'days_interval' => json_encode($request->allowed_days),
                'exclude_days' => json_encode($exclude_days),
                'allowed_roles' => json_encode($request->allowed_roles),
                'description' => $request->description,
                'created_by' => $request->user()->id,
                'created_at' => Carbon::now()
            ];
        }

        // Check if range is already taken
        $overlaps = RoomRate::whereIn('room_type_id', $request->room_types)
                        ->with(['room_type' => function ($q) {
                            $q->select('id', 'code', 'name', 'property_id', 'rack_rate', 'status');
                            $q->with('images');
                            $q->with('property:id,code,name');
                        }])
                        ->selectRaw('r.room_type_id, ANY_VALUE(r.room_rate) as last_room_rate, ANY_VALUE(r.start_datetime) as last_start_datetime, ANY_VALUE(r.end_datetime) as last_end_datetime, ANY_VALUE(r.description) as last_description')
                        ->from(DB::raw('(SELECT * FROM room_rates ORDER BY created_at DESC) r'))
                        ->where( function ($q) use ($start_date, $end_date) {
                            $q->whereDate('start_datetime', '<=', $start_date)
                                ->whereDate('end_datetime', '>=', $end_date);
                        })
                        ->addSelect(['rack_rate' => RoomType::whereColumn('id', 'r.room_type_id')->select('rack_rate')->limit(1)])
                        ->groupBy('r.room_type_id')
                        ->where('status', 'approved')
                        ->get();

        
        // return $new_room_rates_to_save;

        $save = RoomRate::insert($new_room_rates_to_save);

        if (!$save) {
            return response()->json(['error' => 'ROOM_RATE_SAVE_FAIL', 'message' => 'Failed to save room rates'], 400);
        }

        return response()->json([
            // 'overlaps' => $overlaps,
            'new_room_rates' => $new_room_rates_to_save,
            'merged' => array_replace_recursive($new_room_rates_to_save, $overlaps->toArray())
        ], 200);

    }
}
