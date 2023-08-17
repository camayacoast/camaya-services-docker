<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Transportation\CreateTransportationRequest;
use App\Models\Transportation\Transportation;
use Illuminate\Support\Str;

class CreateTransportation extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreateTransportationRequest $request)
    {
        //
        // return $request->all();

        $newTransportation = Transportation::create([
            'name' => $request->name,
            'code' => Str::upper($request->code),
            'type' => $request->type,
            'mode' => $request->mode,
            'description' => $request->description,
            'capacity' => $request->capacity,
            'max_infant' => $request->max_infant,
            'status' => $request->status,
            'current_location' => $request->current_location,
        ]);

        if (!$newTransportation->save()) {
            return response()->json(['error' => 'Could not save transportation.'], 400);
        }

        return response()->json($newTransportation, 200);
    }
}
