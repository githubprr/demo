<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class Group extends Model
{
    use SoftDeletes;
    use Loggable;
    
    protected $table = "groups";

    public function group_user()
    {
        return $this->hasMany(GroupUser::class, 'group_id', 'id');
    }

    public function pending_group_user()
    {
        return $this->hasMany(GroupRequest::class, 'group_id', 'id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')->withTrashed();
    }
}
