<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class PointMaster extends Model
{
    use Loggable;
    
    protected $table = "points_master";
}
