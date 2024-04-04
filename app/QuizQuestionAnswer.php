<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class QuizQuestionAnswer extends Model
{
    use SoftDeletes;
    use Loggable;
	
    protected $table = "quiz_question_answer";

    public function quiz_master()
    {
        return $this->belongsTo(QuizMaster::class, 'quiz_master_id', 'id')->withTrashed();
    }
}
