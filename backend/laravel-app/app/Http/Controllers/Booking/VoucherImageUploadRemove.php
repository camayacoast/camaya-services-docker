<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VoucherImageUploadRemove extends Controller
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
        if (!in_array('*', $request->files_to_remove)) {
            $remove = Storage::delete($request->files_to_remove);
        }

        return response()->json(['remove' => $remove], 200);
    }
}
