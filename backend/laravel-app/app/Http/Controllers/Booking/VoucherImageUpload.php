<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VoucherImageUpload extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // return $request->file('file');
        $path = Storage::putFile('public', $request->file('file'));

        return [
            'path' => env('APP_URL').Storage::url($path),
            'file_name' => basename($path),
        ];
    }
}
