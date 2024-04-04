<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class UomMaster extends Model
{
	use SoftDeletes;
    use Loggable;
	
    protected $table = "uom_master";

    public function items()
    {
        return $this->hasMany(ItemMaster::class, 'uom_id', 'id');
    }
}
