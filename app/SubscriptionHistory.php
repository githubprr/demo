<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class SubscriptionHistory extends Model
{
    use Loggable;
    
    protected $table = "subscription_history";
}
