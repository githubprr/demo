<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class SpecialAddon extends Model
{
    use Loggable;
    
    protected $table = "special_addons";
}
