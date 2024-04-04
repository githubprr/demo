<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class Faq extends Model
{
    use Loggable;
    
    protected $table = "faq";
}
