<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class QuizMaster extends Model
{
    use SoftDeletes;
    use Loggable;
    
    protected $table = "quiz_master";

    public function quiz_question_answer()
    {
        return $this->hasMany(QuizQuestionAnswer::class, 'quiz_master_id', 'id');
    }

    public function quiz_result()
    {
        return $this->hasMany(QuizResult::class, 'quiz_master_id', 'id');
    }

    public function state() {
        return $this->belongsTo(StateMaster::class, 'state_id', 'id')->withTrashed();
    }

    public function district() {
        return $this->belongsTo(DistrictMaster::class, 'district_id', 'id')->withTrashed();
    }

    public function taluka() {
        return $this->belongsTo(TalukaMaster::class, 'taluka_id', 'id')->withTrashed();
    }
}
