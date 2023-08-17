<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Stub;

class UpdateStubCategory extends Controller
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

        $updateStubCategory = Stub::find($request->stub_id);

        if (!$updateStubCategory) {
            return response()->json(['error' => 'STUB_NOT_FOUND'], 400);
        }

        $updateStubCategory->update(['category' => $request->category]);

        return $updateStubCategory->refresh();

    }

}
