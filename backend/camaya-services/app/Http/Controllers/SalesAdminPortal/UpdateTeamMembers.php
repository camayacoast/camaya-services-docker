<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use App\Models\RealEstate\SalesTeam;
use App\Models\RealEstate\SalesTeamMember;

class UpdateTeamMembers extends Controller
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

        $salesTeam = SalesTeam::find($request->team_id);

        if (!$salesTeam) {
            return response()->json(['message' => 'Team not found.'], 404);
        }

        // Remove all members
        SalesTeamMember::where('team_id', $request->team_id)->delete();

        // Check if Team member is the owner
        $team = SalesTeam::where('id', $request->team_id)->first();

        try {
            if ($request->team_member_ids) {
                $getMembersToAdd = User::whereIn('id', $request->team_member_ids)
                                        ->where('id', '!=', $team->owner_id)
                                        ->with('team_member_of.team')
                                        ->get();
    
                $membersToAdd = [];
                
    
                if (isset($getMembersToAdd)) {
                    foreach ($getMembersToAdd as $member) {
                        if ($member['team_member_of']['team']['parent_id'] == null) {
                            $membersToAdd[] = new SalesTeamMember([
                                'user_id' => $member['id'],
                                'role' => 'member'
                            ]);
                        }
                    }
    
                    $salesTeam->members()->saveMany($membersToAdd);
                }
            }
        } catch (exception $e) {
            return response()->json(['error' => 'TEAM_MEMBER_UPDATE_FAILED'],400);
        }


        return response()->json(['message' => 'Sales sub team members updated.',
                'data' => SalesTeam::withCount('members')
                        ->with('members.user.team_member_of.team')
                        ->with('owner.user.roles')
                        ->with('sub_teams.members.user.team_member_of.team')
                        ->where('id', $team->parent_id)
                        ->first()
                ], 200);
    }
}
