<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class ShopItem extends Model
{
    use SoftDeletes;
    use Loggable;
    
    protected $table = "shop_items";

    public function category() {
        return $this->belongsTo(CategoryMaster::class, 'item_categories_id', 'id')->withTrashed();
    }

    public function subcategory() {
        return $this->belongsTo(SubcategoryMaster::class, 'item_sub_categories_id', 'id')->withTrashed();
    }

    public function uom() {
        return $this->belongsTo(UomMaster::class, 'uom_id', 'id')->withTrashed();
    }

    public function company() {
        return $this->belongsTo(User::class, 'companies_id', 'id')->withTrashed();
    }

    public function shop_item_reviews()
    {
        return $this->hasMany(ShopItemReview::class, 'shop_item_id', 'id');
    }
}
