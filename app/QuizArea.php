<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class QuizArea extends Model
{
    use Loggable;
    
    protected $table = "quiz_area";

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'user_id')->withTrashed();
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
