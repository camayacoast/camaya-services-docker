<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use App\Models\RealEstate\SalesTeam;
use App\Models\RealEstate\SalesTeamMember;

class UpdateSalesTeam extends Controller
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

        if ($request->parent_team) {

            $checkIfParentIdExist = SalesTeam::find($request->parent_team);

            if (!$checkIfParentIdExist) {
                return response()->json(['message' => 'Parent team does not exist'], 404);
            }

        }

        $salesTeam = SalesTeam::find($request->id);

        if (!$salesTeam) {
            return response()->json(['message' => 'Team not found.'], 404);
        }

        // Get all sales team members
        $removed_member_ids = SalesTeamMember::where('team_id', $salesTeam->id)->whereNotIn('user_id', $request->members)->pluck('user_id');

        // Remove all members
        SalesTeamMember::where('team_id', $salesTeam->id)->delete();

        // Remove from sub team
        SalesTeamMember::whereIn('user_id', $removed_member_ids)
                            ->where('team_id', '!=', $salesTeam->id)
                            ->delete();

        // Sales Team update team lead
        SalesTeam::whereIn('owner_id', $removed_member_ids)
                ->update([
                    'owner_id' => null
                ]);

        // Create owner
        $getOwnerRecord = User::where('id', $request->owner)->first();

        // Update Team Name
        SalesTeam::where('id', $request->id)->update([
            'name' => $request->team_name,
            'owner_id' => $request->owner
        ]);

        $ownerRecord = SalesTeamMember::create([
            'team_id' => $request->id,
            'user_id' => $getOwnerRecord['id'],
            'role' => 'owner'
        ]);


        if ($request->members) {
            $getMembersToAdd = User::whereIn('id', $request->members)
                            ->where('id', '!=', $request->owner)
                            ->with('team_member_of.team')
                            ->get();

            $membersToAdd = [];

            if (isset($getMembersToAdd)) {
                foreach ($getMembersToAdd as $member) {
                    // if (!isset($member['team_member_of'])) {
                        $membersToAdd[] = new SalesTeamMember([
                            'user_id' => $member['id'],
                            'role' => 'member'
                        ]);
                    // }
                }

                $salesTeam->members()->saveMany($membersToAdd);
            }
        }


        return response()->json(['message' => 'Update Sales team successful.'], 200);
    }
}
