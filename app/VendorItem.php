<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class VendorItem extends Model
{
    use SoftDeletes;
    use Loggable;
	
    protected $table = "vendor_items";

    public function item_master() {
        return $this->belongsTo(ItemMaster::class, 'item_master_id', 'id')->withTrashed();
    }

    public function vendor_item_attributes()
    {
        return $this->hasMany(VendorItemAttributes::class, 'vendor_item_id', 'id');
    }
}
