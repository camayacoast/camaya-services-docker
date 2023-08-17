<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;

class UserList extends Controller
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
        return User::whereIn('user_type', ['admin', 'agent'])
                    ->with('roles:id,name')
                    ->orderBy('first_name', 'ASC')
                    ->orderBy('last_name', 'ASC')
                    ->get();
    }
}
