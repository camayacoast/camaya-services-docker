<?php

namespace App\Http\Controllers\SalesAdminPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;

class SalesAgentList extends Controller
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

        return User::where('user_type', 'agent')
                    ->with('roles')
                    ->with('team_member_of.team')
                    // ->parentTeam()
                    ->subTeam()
                    ->get()
                    ->toArray();
    }
}
