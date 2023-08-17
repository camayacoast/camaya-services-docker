<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\RoomType;

class RoomTypesPerEntityList extends Controller
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
        $entities = ['BPO', 
                    'HOA', 
                    'RE', 
                    'OTA',
                    'SD Rudolph Cortez',
                    'SD Louie Paule',
                    'SD Luz Dizon',
                    'SD John Rizaldy Zuno',
                    'SD Brian Beltran',
                    'SD Jake Tuazon',
                    'SD Joey Bayon',
                    'SD Grace Laxa',
                    'SD Stephen Balbin',
                    'SD Maripaul Milanes',
                    'SD Danny Ngoho',
                    'SD Harry Colo',
                    'SD Lhot Quiambao'
                    ];

        $room_types = RoomType::
            // join('properties', 'properties.id', '=', 'room_types.property_id')
            select(
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
                // 'properties.name as property_name',
                // 'properties.code as property_code',
            )
            ->with('property')
            ->withCount('rooms')
            ->withCount('enabledRooms')
            ->with('images')
            ->orderBy('room_types.property_id')
            ->get();

        $combined = [];

        foreach ($entities as $entity) {

            foreach ($room_types as $room_type) {
                $combined[] = [
                    'room_type' => $room_type,
                    'entity' => $entity,
                ];
            }
            
        }

        return $combined;
    }
}
