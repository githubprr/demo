<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class UserQuiz extends Model
{
    use Loggable;
    
    protected $table = "user_quiz";

    public function quiz_question_answer() {
        return $this->belongsTo(QuizQuestionAnswer::class, 'quiz_question_answer_id', 'id')->withTrashed();
    }
}
