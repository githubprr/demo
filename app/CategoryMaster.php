<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class CategoryMaster extends Model
{
    use SoftDeletes;
    use Loggable;
    
    protected $table = "item_categories";

    public $timestamps = false;

    public function sub_categories()
    {
        return $this->hasMany(SubcategoryMaster::class, 'item_categories_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(ItemMaster::class, 'item_categories_id', 'id');
    }

    public function shop_items()
    {
        return $this->hasMany(ShopItem::class, 'item_categories_id', 'id');
    }

    public function category_group() {
        return $this->belongsTo(CategoryGroupMaster::class, 'item_category_group_id', 'id')->withTrashed();
    }
}
