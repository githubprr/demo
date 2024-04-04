<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class VendorRequisition extends Model
{
    use Loggable;
    
    protected $table = "vendor_requisitions";

    public function vendor() {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function requisition()
    {
        return $this->belongsTo(Requisitions::class, 'requisition_id', 'id')->withTrashed();
    }
}
