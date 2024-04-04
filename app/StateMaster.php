<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class StateMaster extends Model
{
    use SoftDeletes;
    use Loggable;
	
    protected $table = "states";

    public function country() {
        return $this->belongsTo(CountryMaster::class, 'country_id', 'id')->withTrashed();
    }

    public function districts()
    {
        return $this->hasMany(DistrictMaster::class, 'state_id', 'id');
    }
}
