<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class OrderHistory extends Model
{
    use Loggable;
    
    protected $table = "order_history";

    protected $fillable = [
        'order_id',
        'status'
    ];

    public function order() {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
