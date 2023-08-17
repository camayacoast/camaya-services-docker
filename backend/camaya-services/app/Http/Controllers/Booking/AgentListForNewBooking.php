<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;

class AgentListForNewBooking extends Controller
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

        $sales_directors = User::where('user_type', 'agent')
                    ->role('Sales Director')
                    ->with(['owned_team.members' => function ($q) {
                        $q->join('users', 'users.id', '=', 'sales_team_members.user_id');
                        $q->join('sales_teams', 'sales_teams.id', '=', 'sales_team_members.team_id');
                        $q->select(
                            'sales_team_members.user_id',
                            'sales_team_members.team_id',
                            'users.first_name',
                            'users.last_name',
                            'sales_teams.owner_id as sales_director_id',
                        );
                    }])
                    ->get();

        $sales_agents = [];

        foreach ($sales_directors as $sales_director) {
            if (isset($sales_director['owned_team']) && isset($sales_director['owned_team']['members'])) { 
                foreach ($sales_director['owned_team']['members'] as $member) {
                    $sales_agents[] = $member;
                }
            }
        } 

        return [
            'sales_directors' => $sales_directors,
            'sales_agents' => $sales_agents,
        ];
    }
}
