<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class NotificationStatus extends Model
{
    use Loggable;
    
    protected $table = "notification_status";

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function notification() {
        return $this->belongsTo(Notification::class, 'notification_id', 'id')->withTrashed();
    }
}
