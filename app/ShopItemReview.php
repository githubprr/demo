<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class ShopItemReview extends Model
{
    use Loggable;
    
    protected $table = "shop_item_reviews";

    public function shop_item() {
        return $this->belongsTo(ShopItem::class, 'shop_item_id', 'id')->withTrashed();
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }
}
