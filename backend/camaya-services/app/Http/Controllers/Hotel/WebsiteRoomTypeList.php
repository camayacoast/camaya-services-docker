<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomType;

class WebsiteRoomTypeList extends Controller
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
        return RoomType::join('properties', 'properties.id', '=', 'room_types.property_id')
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
            ->withCount('enabledRooms as rooms_count')
            ->where('room_types.status', 'enabled')
            ->orderBy('room_types.property_id')
            ->with('images')
            ->get();
    }
}
