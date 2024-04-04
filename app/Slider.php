<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class Slider extends Model
{
    use Loggable;
    
    protected $table = "sliders";

    public function blog() {
        return $this->hasOne(Blog::class, 'id', 'blog_id')->withTrashed();
    }
}
