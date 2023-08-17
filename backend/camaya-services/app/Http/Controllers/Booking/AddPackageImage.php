<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\Booking\PackageImage;

class AddPackageImage extends Controller
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

        $newImage = PackageImage::create([
            'package_id' => $request->id,
            'image_path' => env('APP_URL').Storage::url($path),
            'cover' => 'no',
        ]);

        // set all images cover to no
        PackageImage::where('package_id', $request->id)->update(['cover' => 'no']);

        // set the last record cover to yes
        $lastImageRecord = PackageImage::where('package_id', $request->id)->orderBy('created_at', 'desc')->first();    
        if ($lastImageRecord) {
            $lastImageRecord->update(['cover' => 'yes']);
        }

        return $newImage->refresh();
    }
}
