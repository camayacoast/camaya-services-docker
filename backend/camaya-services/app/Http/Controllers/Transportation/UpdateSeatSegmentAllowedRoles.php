<?php

namespace App\Http\Controllers\Transportation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Transportation\SeatSegment;
use App\Models\Transportation\SeatSegmentAllow;
use App\Models\Main\Role;

class UpdateSeatSegmentAllowedRoles extends Controller
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

        $seat_segment = SeatSegment::find($request->id);

        if (!$seat_segment) {
            return response()->json(['error' => 'ERROR', 'message' => "Error"], 400);
        }

        // Remove all allowed roles
        SeatSegmentAllow::where('seat_segment_id', $request->id)->whereNotNull('role_id')->delete();


        $role_ids = Role::whereIn('id', $request->new_roles)->select('id as role_id')->get()->toArray();

        $seat_segment->allowed_roles()->createMany($role_ids);

        return $seat_segment;
    }
}
