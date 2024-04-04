<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class ForumQuestion extends Model
{
    use SoftDeletes;
    use Loggable;
    
    protected $table = "forum_questions";

    protected $fillable = [
        'company_id',
        'item_id',
        'subject',
        'question',
        'image',
        'visibility',
        'user_id',
		'status',
        'all_company'
    ];

    public function forum_answers()
    {
        return $this->hasMany(ForumAnswer::class, 'forum_question_id', 'id')->orderBy('id','desc');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')->withTrashed();
    }

    public function company() {
        return $this->belongsTo(User::class, 'company_id', 'id')->withTrashed();
    }

    public function item() {
        return $this->belongsTo(ItemMaster::class, 'item_id', 'id')->withTrashed();
    }
}
