<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class VillageMaster extends Model
{
    use SoftDeletes;
    use Loggable;
	
    protected $table = "villages";

    public function taluka() {
        return $this->belongsTo(TalukaMaster::class, 'taluka_id', 'id')->withTrashed();
    }

    public function users()
    {
        return $this->hasMany(User::class, 'village_id', 'id');
    }
}
