<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\Booking\VoucherImage;

class AddVoucherImage extends Controller
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

        $newImage = VoucherImage::create([
            'voucher_id' => $request->id,
            'image_path' => env('APP_URL').Storage::url($path),
            'cover' => 'no',
        ]);

        // set all images cover to no
        VoucherImage::where('voucher_id', $request->id)->update(['cover' => 'no']);

        // set the first record cover to yes
        $firstImageRecord = VoucherImage::where('voucher_id', $request->id)->first();    
        if ($firstImageRecord) {
            $firstImageRecord->update(['cover' => 'yes']);
        }

        return $newImage->refresh();
    }
}
