<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class Blog extends Model
{
    use SoftDeletes;
    use Loggable;
    
    protected $table = "blogs";

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
