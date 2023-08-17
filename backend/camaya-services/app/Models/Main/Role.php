<?php

namespace App\Models\Main;


use Spatie\Permission\Models\Role as Model;

class Role extends Model
{
    //
    protected $connection = 'mysql';
    protected $table = 'roles';

    
}
