<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class DistrictMaster extends Model
{
    use SoftDeletes;
    use Loggable;
	
    protected $table = "districts";

    protected $fillable = ['name','state_id'];

    public function state() {
        return $this->belongsTo(StateMaster::class, 'state_id', 'id')->withTrashed();
    }

    public function talukas()
    {
        return $this->hasMany(TalukaMaster::class, 'district_id', 'id');
    }
}
