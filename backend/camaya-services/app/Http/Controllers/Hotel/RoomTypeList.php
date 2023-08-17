<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomType;
use App\Models\Hotel\RoomReservation;

class RoomTypeList extends Controller
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
            ->withCount('rooms')
            ->withCount('enabledRooms')
            ->with('images')
            ->orderBy('room_types.property_id')
            ->get();

        if ($request->date) {

            foreach ($room_types as $key => $room_type) {

                /**
                 * Get blocked rooms
                 */
                $blocked_rooms = RoomReservation::whereRaw("DATE_FORMAT(start_datetime, '%Y-%m-%d') <= ?", $request->date)
                            ->whereRaw("DATE_FORMAT(end_datetime, '%Y-%m-%d') > ?", $request->date)
                            ->where('status', 'blackout')
                            ->where('room_type_id', $room_type['id'])
                            ->get();


                $room_types[$key]['blocked_rooms'] = count($blocked_rooms);

            }
        
        }

        return $room_types;
    }
}
