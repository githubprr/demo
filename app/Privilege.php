<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Privilege extends Model
{
    protected $table = "privileges";

    public function privilege_roles(){
        return $this->hasMany(PrivilegeRoles::class, 'privilege_id', 'id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'privileges', 'id');
    }
}
