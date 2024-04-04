<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class ForumAnswer extends Model
{
    use Loggable;
    
    protected $table = "forum_answers";

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')->withTrashed();
    }

    public function forum_question()
    {
        return $this->hasOne(ForumQuestion::class, 'id', 'forum_question_id')->withTrashed();
    }
}
