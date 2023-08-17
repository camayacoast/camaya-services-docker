<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use App\Models\RealEstate\SalesTeam;
use App\Models\RealEstate\SalesTeamMember;

class AddSubTeam extends Controller
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
                !$request->user()->hasPermissionTo('SalesAdminPortal.Create.SalesTeam')
            ) {
                return response()->json(['message' => 'Unauthorized.'], 400);
            }
        }

        $newSalesSubTeam = SalesTeam::create([
            'parent_id' => $request->team_id,
            'owner_id' => null,
            'name' => 'Sub-team-name',
            'created_by' => $request->user()->id,
        ]);


        if (!$newSalesSubTeam) {
            return response()->json(['message' => 'Team not created.'], 400);
        }

        return response()->json(['message' => 'New Sales sub team created.',
                'data' => SalesTeam::withCount('members')
                        ->with('members.user')
                        ->with('owner.user.roles')
                        ->with('sub_teams.members.user')
                        ->where('id', $request->team_id)
                        ->first()
                ], 200);
    }
}
