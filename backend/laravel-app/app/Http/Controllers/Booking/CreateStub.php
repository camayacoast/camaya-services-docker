<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Booking\Stub;
use Carbon\Carbon;

class CreateStub extends Controller
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
        
        // Check if stub type exist
        $stub = Stub::where('type', $request->type)->first();

        if ($stub) {
            return response()->json(['error'=>'STUB_ALREADY_EXISTS', 'message'=>'Stub type already exist'], 400);
        }

        $newStub = Stub::create([
            'type' => $request->type,
            'interfaces' => [$request->interfaces],
            'mode' => $request->mode,
            'count' => $request->count,
            'category' => $request->category,
            'starttime' => Carbon::parse($request->starttime)->setTimezone('Asia/Manila')->isoFormat('HH:mm:ss'),
            'endtime' => Carbon::parse($request->endtime)->setTimezone('Asia/Manila')->isoFormat('HH:mm:ss'),
        ]);

        if (!$newStub) {
            return response()->json(['error'=>'FAILED_TO_CREATE_STUB', 'message'=>'Failed to create stub.'], 400);
        }

        // return response()->json(['statys'=>'OK', 'message'=>'Added new stub.'], 200);
        return response()->json($newStub, 200);
    }
}
