<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class FormMaster extends Model
{
    use SoftDeletes;
    use Loggable;
	
    protected $table = "form";

    public $timestamps = false;

    public function items()
    {
        return $this->hasMany(ItemMaster::class, 'form_id', 'id');
    }
}
