<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class TalukaMaster extends Model
{
    use SoftDeletes;
    use Loggable;
	
    protected $table = "talukas";

    public function district() {
        return $this->belongsTo(DistrictMaster::class, 'district_id', 'id')->withTrashed();
    }

    public function villages()
    {
        return $this->hasMany(VillageMaster::class, 'taluka_id', 'id');
    }
}
