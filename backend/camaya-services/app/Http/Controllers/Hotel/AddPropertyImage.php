<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\Hotel\RoomTypeImage;

class AddPropertyImage extends Controller
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

        $path = $request->file('file')->store('public');

        $newImage = RoomTypeImage::create([
            'room_type_id' => $request->roomTypeId,
            'image_path' => env('APP_URL').Storage::url($path),
            'cover' => 'no',
        ]);

        // set all images cover to no
        RoomTypeImage::where('room_type_id', $request->roomTypeId)->update(['cover' => 'no']);

        // set the first record cover to yes
        $firstImageRecord = RoomTypeImage::where('room_type_id', $request->roomTypeId)->first();    
        if ($firstImageRecord) {
            $firstImageRecord->update(['cover' => 'yes']);
        }

        return $newImage->refresh();
    }
}
