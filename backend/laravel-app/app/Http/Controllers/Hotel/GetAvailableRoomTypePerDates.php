<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use DB;
use Carbon\Carbon;
use App\Models\Hotel\RoomAllocation;
use App\Models\Hotel\RoomType;
use App\Models\Hotel\RoomReservation;

class GetAvailableRoomTypePerDates extends Controller
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

        $start_date =  Carbon::parse($request->dates)->setTimezone('Asia/Manila');

        $room_types = RoomType::join('properties', 'properties.id', '=', 'room_types.property_id')
            ->select(
                'room_types.id',
                'room_types.property_id',
                'room_types.name',
                'room_types.code',
                'room_types.description',
                'room_types.capacity',
                'room_types.max_capacity',
                'room_types.rack_rate',
                'room_types.cover_image_path',
                'room_types.status',
                'properties.name as property_name',
                'properties.code as property_code',             
            )
            ->where('properties.code', 'AF')
            
            ->with(['allocations' => function ($q) use ($start_date) {
                $q->whereDate('date', '=', $start_date);
            }])
            ->withCount('rooms')
            ->withCount('enabledRooms')
            ->orderBy('room_types.property_id')
            ->with('images')
            ->get();

    
        $data = [];

        foreach ($room_types as $room_type) {

            $taken = 0;

            foreach ($room_type->allocations as $rta) {
                $taken = $taken + $rta['allocation'];
            }

            $data[] = [
                'id' => $room_type->id,
                'property_id' => $room_type->property_id,
                'name' => $room_type->name,
                'code' => $room_type->code,
                // 'description' => $room_type->description,
                // 'capacity' => $room_type->capacity,
                // 'max_capacity' => $room_type->max_capacity,
                // 'rack_rate' => $room_type->rack_rate,
                // 'cover_image_path' => $room_type->cover_image_path,
                'status' => $room_type->status,
                'property_name' => $room_type->property_name,
                'property_code' => $room_type->property_code,
                // 'booked_rooms' => $room_type->booked_rooms ?? 0,
                'rooms_count' => $room_type->rooms_count,
                'enabled_rooms_count' => $room_type->enabled_rooms_count,
                // 'images' => $room_type->images,
                'allocations' => $room_type->allocations,
                'available' => $room_type->enabled_rooms_count - $taken,
            ];
        }

        return $data;
    }
}
