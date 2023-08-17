<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Spatie\Permission\Models\Permission;
// use Spatie\Permission\Models\Role;

class Permissions extends Controller
{
    
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {
        return Permission::orderByRaw("SUBSTRING_INDEX(name, '.', -1)", "ASC")
                    ->pluck('name');
    }
}
