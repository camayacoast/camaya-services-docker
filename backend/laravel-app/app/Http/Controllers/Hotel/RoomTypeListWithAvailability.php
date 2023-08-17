<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomType;
use App\Models\Hotel\RoomReservation;
use Illuminate\Support\Facades\DB;

class RoomTypeListWithAvailability extends Controller
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
        $arrival = $request->arrival." 12:00:00";
        $departure = $request->departure." 11:00:00";

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
            ->addSelect([
                'booked_rooms' => RoomReservation::selectRaw('count(*) as booked')
                                                ->where(function ($query) use ($arrival, $departure) {
                                                    $query->where(function ($query) use ($arrival, $departure) {
                                                        $query->where('start_datetime', '<=', $arrival)
                                                            ->where('end_datetime', '>=', $arrival);
                                                    })->orWhere(function ($query) use ($arrival, $departure) {
                                                        $query->where('start_datetime', '<=', $departure)
                                                            ->where('end_datetime', '>=', $departure);
                                                    })->orWhere(function ($query) use ($arrival, $departure) {
                                                        $query->where('start_datetime', '>=', $arrival)
                                                            ->where('end_datetime', '<=', $departure);
                                                    });
                                                })
                                        ->whereColumn('room_type_id', 'room_types.id')
                                        ->groupBy('room_type_id')
                ,
                // 'booked' => DB::raw('IFNULL(`booked_rooms`, 0)')
            ])
            ->with(['allocations' => function ($q) use ($request) {
                $q->whereDate('date', '>=', $request->arrival);
                $q->whereDate('date', '<', $request->departure);
            }])
            ->withCount('rooms')
            ->withCount('enabledRooms')
            ->orderBy('room_types.property_id')
            ->with('images')
            ->get();

    
        $data = [];

        foreach ($room_types as $room_type) {
            $data[] = [
                'id' => $room_type->id,
                'property_id' => $room_type->property_id,
                'name' => $room_type->name,
                'code' => $room_type->code,
                'description' => $room_type->description,
                'capacity' => $room_type->capacity,
                'max_capacity' => $room_type->max_capacity,
                'rack_rate' => $room_type->rack_rate,
                'cover_image_path' => $room_type->cover_image_path,
                'status' => $room_type->status,
                'property_name' => $room_type->property_name,
                'property_code' => $room_type->property_code,
                'booked_rooms' => $room_type->booked_rooms ?? 0,
                'rooms_count' => $room_type->rooms_count,
                'images' => $room_type->images,
                'allocations' => $room_type->allocations,
            ];
        }

        return $data;

    }
}
