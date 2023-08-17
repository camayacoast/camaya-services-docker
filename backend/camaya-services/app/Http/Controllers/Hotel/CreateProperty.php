<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hotel\Property;
use App\Models\Hotel\RoomType;
use App\Models\Hotel\RoomTypeImage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use App\Http\Requests\Hotel\CreatePropertyRequest;

class CreateProperty extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreatePropertyRequest $request)
    {
        //

        $newProperty = Property::create([
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

        if (isset($request->room_types)) {

            // $room_types_to_save = [];

            foreach ($request->room_types as $room_type) {

                $newRoomType = RoomType::create([
                    'property_id' => $newProperty->id,
                    'name' => $room_type['name'],
                    'code' => Str::upper($room_type['code']),
                    'description' => $room_type['description'] ?? null,
                    'capacity' => $room_type['capacity'],
                    'max_capacity' => $room_type['max_capacity'],
                    'rack_rate' => $room_type['rack_rate'],
                    'cover_image_path' => $room_type['cover_image_path'] ?? null,
                ]);

                // Save room type images
                if (isset($room_type['images'])) {
                    $room_type_images_to_save = [];

                    foreach ($room_type['images'] as $key => $image) {
                        $room_type_images_to_save[] = RoomTypeImage::create([
                            'room_type_id' => $newRoomType->id,
                            'image_path' => env('APP_URL').Storage::url(basename($image['response']['path'])),
                            'cover' => $key == 0 ? 'yes' : 'no',
                        ]);
                    }

                    // $newProperty->room_types()->images()->saveMany($room_type_images_to_save);
                }

            }

            // $newProperty->room_types()->saveMany($room_types_to_save);

        }

        $newProperty->refresh();
        return $newProperty->load('room_types.images');
    }
}
