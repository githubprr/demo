<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class VendorItemAttributes extends Model
{
    use Loggable;
    
    protected $table = "vendor_item_attributes";

    public function vendor_item() {
        return $this->belongsTo(VendorItem::class, 'vendor_item_id', 'id')->withTrashed();
    }

    public function uom() {
        return $this->belongsTo(UomMaster::class, 'uom_id', 'id')->withTrashed();
    }

    public function form() {
        return $this->belongsTo(FormMaster::class, 'form_id', 'id')->withTrashed();
    }
}
