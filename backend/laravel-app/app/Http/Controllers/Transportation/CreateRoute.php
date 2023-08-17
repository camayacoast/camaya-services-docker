<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\Route as TransportationRoute;

class CreateRoute extends Controller
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

        $exists = TransportationRoute::where('origin_id', $request->origin)->where('destination_id', $request->destination)->exists();
        
        if ($exists) {
            return response()->json(['error' => 'This route already exist in our records.'], 400);
        }

        $newRoute = TransportationRoute::create([
            'origin_id' => $request->origin,
            'destination_id' => $request->destination,
        ]);

        if (!$newRoute->save()) {
            return response()->json(['error' => 'Could not save route.'], 400);
        }

        $savedRoute = TransportationRoute::with('origin')->with('destination')->where('id', $newRoute->id)->first();

        return response()->json($savedRoute, 200);
    }
}
