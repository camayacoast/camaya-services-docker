<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\Booking\PackageImage;

class RemovePackageImage extends Controller
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

        $packageImage = PackageImage::find($request->route('id'));
        $packageImageDetails = $packageImage;
        $fileToDelete = basename($packageImage->image_path);

        $fileDeleted = Storage::delete("public/$fileToDelete");
        if ($fileDeleted) {
            $packageImage->delete();
            
            return response()->json(['remove' => $packageImageDetails], 200);
        }

        return response()->json(['error' => 'File not deleted'], 500);
    }
}
