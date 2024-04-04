<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class CompanyReview extends Model
{
    use Loggable;
    
    protected $table = "company_reviews";

    public function company() {
        return $this->belongsTo(User::class, 'company_id', 'id')->withTrashed();
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }
}
