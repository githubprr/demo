<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class PurchaseOrder extends Model
{
    use Loggable;
    
    protected $table = "podetails";

    public function enquiry() {
        return $this->belongsTo(Enquiries::class, 'enquiry_id', 'id')->withTrashed();
    }

    public function sales_orders()
    {
        return $this->hasMany(SalesOrder::class, 'purchase_order_id', 'id');
    }
}
