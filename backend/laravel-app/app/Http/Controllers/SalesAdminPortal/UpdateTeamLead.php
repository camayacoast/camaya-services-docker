<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\SalesTeam;

class UpdateTeamLead extends Controller
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

        if (!$request->user()->hasRole(['super-admin'])) {
            if ( 
                $request->user()->user_type != 'admin' ||
                !$request->user()->hasPermissionTo('SalesAdminPortal.Edit.SalesTeam')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        try {
            SalesTeam::where('id', $request->team_id)
                ->update([
                    'owner_id' => $request->team_lead_id
                ]);
        } catch (exception $e) {
            return response()->json(['error' => 'TEAM_LEAD_UPDATE_FAILED'],400);
        }

        $team = SalesTeam::where('id', $request->team_id)->first();

        return response()->json(['message' => 'Sales sub team lead updated.',
                'data' => SalesTeam::withCount('members')
                        ->with('members.user')
                        ->with('owner.user.roles')
                        ->with('sub_teams.members.user')
                        ->where('id', $team->parent_id)
                        ->first()
                ], 200);
        
    }
}
