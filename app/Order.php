<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class Order extends Model
{
    use Loggable;
    
    protected $table = "orders";

    protected $fillable = [
        'item_id',
        'price',
        'qty',
        'discount',
        'grains',
		'user_id',
        'user_address_id',
        'status',
        'payment_processor',
        'payment_method',
        'payment_id',
		'is_refund'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function item() {
        return $this->belongsTo(ShopItem::class, 'item_id', 'id')->withTrashed();
    }

    public function address() {
        return $this->belongsTo(UserAddress::class, 'user_address_id', 'id')->withTrashed();
    }

    public function order_history() {
        return $this->hasMany(OrderHistory::class, 'order_id', 'id');
    }
}
