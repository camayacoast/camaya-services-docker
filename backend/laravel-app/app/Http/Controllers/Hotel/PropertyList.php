<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\Property;

class PropertyList extends Controller
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
        return Property::with(['room_types' => function ($q) {
                        $q->withCount('enabledRooms');
                        $q->with('images');
                    }])
                    ->with(['rooms' => function ($q) {
                        $q->join('room_types', 'room_types.id', '=', 'rooms.room_type_id');
                        $q->select(
                            'rooms.id', 
                            'rooms.property_id', 
                            'rooms.room_type_id', 
                            'room_types.name', 
                            'room_types.code', 
                            'number', 
                            'rooms.description', 
                            'rooms.enabled',
                            'rooms.room_status',
                            'rooms.fo_status',
                        );
                    }])
                    ->get();
                    
    }
}
