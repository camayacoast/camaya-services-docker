<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\SeatSegment;
use App\Models\Transportation\SeatSegmentAllow;
use App\User;

class UpdateSeatSegmentAllowedUsers extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        
        $seat_segment = SeatSegment::find($request->id);

        if (!$seat_segment) {
            return response()->json(['error' => 'ERROR', 'message' => "Error"], 400);
        }

        // Remove all allowed roles
        SeatSegmentAllow::where('seat_segment_id', $request->id)->whereNotNull('user_id')->delete();


        $user_ids = User::whereIn('id', $request->new_users)->select('id as user_id')->get()->toArray();

        $seat_segment->allowed_users()->createMany($user_ids);

        return $seat_segment;
    }
}
