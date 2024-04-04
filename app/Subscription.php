<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class Subscription extends Model
{
    use Loggable;
    
    protected $table = "subscriptions";

    public function coupon() {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id')->withTrashed();
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }
}
