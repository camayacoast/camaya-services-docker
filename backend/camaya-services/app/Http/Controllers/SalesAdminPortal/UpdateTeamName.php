<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\SalesTeam;

class UpdateTeamName extends Controller
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
        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.Edit.SalesTeam')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        if (!$request->name) {
            return response()->json(['error' => 'TEAM_NAME_UPDATE_FAILED'],400);
        }

        try {
            SalesTeam::where('id', $request->team_id)
                ->update([
                    'name' => $request->name
                ]);
        } catch (exception $e) {
            return response()->json(['error' => 'TEAM_NAME_UPDATE_FAILED'],400);
        }


        return 'OK';
    }
}
