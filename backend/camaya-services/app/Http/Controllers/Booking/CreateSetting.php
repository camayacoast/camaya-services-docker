<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Setting;

class CreateSetting extends Controller
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

        $newSetting = Setting::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'type' => $request->type,
            'value' => $request->value,
        ]);

        if (!$newSetting) {
            return response()->json(['error' => 'SETTING_SAVE_FAILED', 'message' => 'Failed to save setting.'], 400);
        }

        return response()->json($newSetting, 200);
    }
}
