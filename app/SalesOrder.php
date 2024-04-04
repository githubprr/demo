<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class SalesOrder extends Model
{
    use Loggable;
    
    protected $table = "sales_order";

    public function purchase_order() {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'sales_order_id', 'id');
    }
}
