<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\Room;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoomList extends Controller
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
        $user = User::where('id', '=', auth()->user()->id)->with('roles')->first();
        $can_edit = 0;

        if ($request->user()->hasRole(['super-admin']) || $request->user()->hasPermissionTo('Hotel.UpdateRoomStatus.Room')) {
            $can_edit = 1;
        }
             
        return Room::join('room_types', 'room_types.id', '=', 'rooms.room_type_id')
            ->join('properties', 'properties.id', '=', 'room_types.property_id')
            ->select(
                'rooms.id as room_id',
                'rooms.number',
                'rooms.room_status',
                'rooms.fo_status',
                'rooms.reservation_status',
                'rooms.enabled',
                'room_types.id as room_type_id',
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
                'properties.code as property_code'                
            )
            ->selectRaw("'" . $can_edit . "' as can_edit")
            ->where( function ($query) use ($request) {
                if ($request->calendarView == true) {
                    $query->where('enabled', 1);
                }
            })
            ->get();
    }
}
