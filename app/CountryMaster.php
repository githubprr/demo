<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class CountryMaster extends Model
{
    use SoftDeletes;
    use Loggable;
	
    protected $table = "countries";

    public function states()
    {
        return $this->hasMany(StateMaster::class, 'country_id', 'id');
    }
}
