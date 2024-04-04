<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class SubcategoryMaster extends Model
{
    use SoftDeletes;
    use Loggable;
    
    protected $table = "item_sub_categories";

    public $timestamps = false;

    public function category() {
        return $this->belongsTo(CategoryMaster::class, 'item_categories_id', 'id')->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(ItemMaster::class, 'item_sub_categories_id', 'id');
    }

    public function shop_items()
    {
        return $this->hasMany(ShopItem::class, 'item_sub_categories_id', 'id');
    }
}
