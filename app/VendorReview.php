<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class VendorReview extends Model
{
    use Loggable;
    
    protected $table = "vendor_reviews";

    public function shop_item() {
        return $this->belongsTo(User::class, 'vendor_id', 'id')->withTrashed();
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }
}
