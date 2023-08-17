<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomType;

class RoomTypeOnlyList extends Controller
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
                'room_types.property_id',
                'room_types.name',
                'room_types.code',
                'room_types.status',
                'properties.name as property_name',
                'properties.code as property_code',
            )
            ->where('room_types.status', 'enabled')
            ->withCount('enabledRooms')
            ->orderBy('room_types.property_id')
            ->get();


        return $room_types;
    }
}
