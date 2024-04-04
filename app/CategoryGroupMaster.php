<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;

class CategoryGroupMaster extends Model
{
    use SoftDeletes;
    use Loggable;
	
    protected $table = "item_category_groups";

    public function categories()
    {
        return $this->hasMany(CategoryMaster::class, 'item_category_group_id', 'id');
    }
}
