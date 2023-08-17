<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\Booking\ProductImage;

class RemoveProductImage extends Controller
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

        $productImage = ProductImage::find($request->route('id'));
        $productImageDetails = $productImage;
        $fileToDelete = basename($productImage->image_path);

        $fileDeleted = Storage::delete("public/$fileToDelete");
        if ($fileDeleted) {
            $productImage->delete();
            
            return response()->json(['remove' => $productImageDetails], 200);
        }

        return response()->json(['error' => 'File not deleted'], 500);
    }
}
