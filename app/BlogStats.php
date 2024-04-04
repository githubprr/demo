<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class BlogStats extends Model
{
    use Loggable;
    
    protected $table = "blog_stats";

    public function blog() {
        return $this->belongsTo(Blogs::class, 'blog_id', 'id')->withTrashed();
    }
}
