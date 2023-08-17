<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\Hotel\RoomTypeImage;

class RemovePropertyImage extends Controller
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
        // return $request->route('id');

        $image = RoomTypeImage::find($request->route('id'));
        $imageDetails = $image;
        $fileToDelete = basename($image->image_path);

        $fileDeleted = Storage::delete("public/$fileToDelete");
        if ($fileDeleted) {
            $image->delete();
            
            return response()->json(['remove' => $imageDetails], 200);
        }

        return response()->json(['error' => 'File not deleted'], 500);
    }
}
