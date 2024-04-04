<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class Cart extends Model
{
    use Loggable;
    
    protected $table = "cart";

    protected $fillable = [
        'item_id',
        'qty',
		'user_id'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function item() {
        return $this->belongsTo(ShopItem::class, 'item_id', 'id')->withTrashed();
    }
}
