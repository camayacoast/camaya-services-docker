<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Location;
use Illuminate\Support\Str;

class CreateLocation extends Controller
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
        $newLocation = Location::create([
            'name' => $request->name,
            'code' => Str::upper($request->code),
            'description' => $request->description,
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'icon' => $request->icon,
        ]);

        if (!$newLocation->save()) {
            return response()->json(['error' => 'Could not save location.'], 400);
        }

        return response()->json($newLocation, 200);
    }
}
