<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\Property;
use App\Models\Hotel\RoomType;
use App\Models\Hotel\RoomTypeImage;
use Illuminate\Support\Str;
use App\Http\Requests\Hotel\CreatePropertyRequest;

class UpdateProperty extends Controller
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
        
        $updatedProperty = Property::find($request->id);
        
        $updatedProperty->update([
            'name' => $request->name,
            'code' => Str::upper($request->code),
            'type' => $request->type,
            'address' => $request->address,
            'phone_number' => $request->phone_number,
            'floors' => $request->floors,
            'cover_image_path' => $request->cover_image_path,
            'description' => $request->description,
            'status' => $request->status,                       
        ]);

        $updatedProperty->refresh();


        if (isset($request->room_types)) {

            foreach ($request->room_types as $room_type) {

                if (isset($room_type['id'])) {
                    RoomType::find($room_type['id'])->update([
                        // 'property_id' => $newProperty->id,
                        'name' => $room_type['name'],
                        'code' => Str::upper($room_type['code']),
                        'description' => $room_type['description'] ?? null,
                        'capacity' => $room_type['capacity'],
                        'max_capacity' => $room_type['max_capacity'],
                        'rack_rate' => $room_type['rack_rate'],
                        'cover_image_path' => $room_type['cover_image_path'] ?? null,
                    ]);

                    // Save room type images
                    // if (isset($room_type['images'])) {
                    //     $existingRoomTypeImages = RoomTypeImage::where('room_type_id', $room_type['id'])->get()->pluck('image_path')->toArray();
                    //     $existing_room_type_images_to_be_maintained = [];
                    //     $room_type_images_to_save = [];

                    //     foreach ($room_type['images'] as $key => $image) {
                    //         if (in_array($image['response']['path'], $existingRoomTypeImages)) {
                    //             $existing_room_type_images_to_be_maintained[] = $image['response']['path'];
                    //         } else {
                    //             $room_type_images_to_save[] = new RoomTypeImage([
                    //                 'room_type_id' => $newRoomType->id,
                    //                 'image_path' => $image['response']['path'],
                    //                 'cover' => 'no',
                    //             ]);
                    //         }
                    //     }

                    //     RoomTypeImage::where('room_type_id', $room_type['id'])
                    //         ->whereNotIn('image_path', $existing_room_type_images_to_be_maintained)
                    //         ->delete();

                    //     if ($room_type_images_to_save) {
                    //         RoomTypeImage::where('room_type_id', $room_type['id'])
                    //             ->saveMany($room_type_images_to_save);
                    //     }

                    //     // set all images cover to no
                    //     RoomTypeImage::where('room_type_id', $room_type['id'])
                    //         ->update(['cover' => 'no']);

                    //     // set the first record cover to yes
                    //     $firstRoomTypeImageRecord = RoomTypeImage::where('room_type_id', $room_type['id'])->first();
                        
                    //     if ($firstRoomTypeImageRecord) {
                    //         $firstRoomTypeImageRecord->update(['cover' => 'yes']);
                    //     }
                    // }
                } else {                
                    $newRoomType = RoomType::create([
                        'property_id' => $updatedProperty->id,
                        'name' => $room_type['name'],
                        'code' => Str::upper($room_type['code']),
                        'description' => $room_type['description'] ?? null,
                        'capacity' => $room_type['capacity'],
                        'max_capacity' => $room_type['max_capacity'],
                        'rack_rate' => $room_type['rack_rate'],
                        'cover_image_path' => $room_type['cover_image_path'] ?? null,
                    ]);

                    // Save room type images
                    if (isset($room_type['images']) && count($room_type['images'])) {
                        foreach ($room_type['images'] as $key => $image) {
                            $room_type_images_to_save[] = RoomTypeImage::create([
                                'room_type_id' => $newRoomType->id,
                                'image_path' => $image['response']['path'],
                                'cover' => $key == 0 ? 'yes' : 'no',
                            ]);
                        }
                    }
                }

            } //endforeach
        
        }

        return $updatedProperty->load('room_types.images')->refresh();
    }
}
