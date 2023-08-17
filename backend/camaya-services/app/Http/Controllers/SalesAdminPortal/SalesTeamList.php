<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RealEstate\SalesTeam;

class SalesTeamList extends Controller
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

        return SalesTeam::withCount('members')
                        ->with(['members.user' => function ($q) {
                            $q->with('team_member_of.team');
                            $q->subTeam();
                        }])
                        ->with('owner.user.roles')
                        ->with('sub_teams.members.user.team_member_of.team')
                        ->whereNull('parent_id')
                        ->get();

                        

    }
}
