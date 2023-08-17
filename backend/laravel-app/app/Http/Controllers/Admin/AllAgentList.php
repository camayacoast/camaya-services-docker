<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;

class AllAgentList extends Controller
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

        // 'POC Agent', 'Property Consultant', 'Sales Director', 'Sales Manager'
        $list = ['POC Agent', 'Property Consultant', 'Sales Director', 'Sales Manager'];

        return User::whereIn('user_type', ['admin', 'agent'])
                    ->whereHas('roles', function ($q) use ($list) {
                        $q->whereIn('name', $list);
                    })
                    ->with('roles:id,name')
                    ->orderBy('first_name', 'ASC')
                    ->orderBy('last_name', 'ASC')
                    ->get();
    }
}
