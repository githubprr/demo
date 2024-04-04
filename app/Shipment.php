<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class Shipment extends Model
{
    use Loggable;
    
    protected $table = "shipment_details";

    public function sales_order() {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }
}
