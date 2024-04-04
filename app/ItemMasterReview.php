<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class ItemMasterReview extends Model
{
    use Loggable;
    
    protected $table = "item_master_reviews";

    public function item_master() {
        return $this->belongsTo(ItemMaster::class, 'item_master_id', 'id')->withTrashed();
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }
}
