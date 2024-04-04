<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class GroupRequest extends Model
{
    use Loggable;
    
    protected $table = "group_request";

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function group() {
        return $this->belongsTo(Group::class, 'group_id', 'id')->withTrashed();
    }
}
