<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use App\Models\RealEstate\SalesTeam;
use App\Models\RealEstate\SalesTeamMember;

class NewSalesTeam extends Controller
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

        if ($request->parent_team) {

            $checkIfParentIdExist = SalesTeam::find($request->parent_team);

            if (!$checkIfParentIdExist) {
                return response()->json(['message' => 'Parent team does not exist'], 404);
            }

        }

        $checkIfNameExists = SalesTeam::where('name', $request->team_name)->first();

        if ($checkIfNameExists) {
            return response()->json(['message' => 'Team name already exists.'], 400);
        }

        $newSalesTeam = SalesTeam::create([
            'parent_id' => $request->parent_team,
            'owner_id' => $request->owner,
            'name' => $request->team_name,
            'created_by' => $request->user()->id,
        ]);

        if (!$newSalesTeam) {
            return response()->json(['message' => 'Team not created.'], 400);
        }

        // Create owner
        $getOwnerRecord = User::where('id', $request->owner)->first();

        $ownerRecord = SalesTeamMember::create([
            'team_id' => $newSalesTeam['id'],
            'user_id' => $getOwnerRecord['id'],
            'role' => 'owner'
        ]);


        if ($request->members) {
            $getMembersToAdd = User::whereIn('id', $request->members)
                            ->where('id', '!=', $request->owner)
                            ->with('team_member_of')
                            ->get();

            $membersToAdd = [];

            if (isset($getMembersToAdd)) {
                foreach ($getMembersToAdd as $member) {
                    if (!isset($member['team_member_of'])) {
                        $membersToAdd[] = new SalesTeamMember([
                            'user_id' => $member['id'],
                            'role' => 'member'
                        ]);
                    }
                }

                $newSalesTeam->members()->saveMany($membersToAdd);
            }
        }


        return response()->json(['message' => 'New Sales team created.'], 200);

    }
}
