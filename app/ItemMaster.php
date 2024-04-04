<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class ItemMaster extends Model
{
    use SoftDeletes;
    use Loggable;
    
    protected $table = "items_master";

    public function category_group() {
        return $this->belongsTo(CategoryGroupMaster::class, 'item_category_group_id', 'id')->withTrashed();
    }

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

    public function vendor() {
        return $this->belongsTo(User::class, 'vendors_id', 'id')->withTrashed();
    }

    public function item_master_reviews()
    {
        return $this->hasMany(ItemMasterReview::class, 'item_master_id', 'id');
    }
}
