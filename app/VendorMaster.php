<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorMaster extends Model
{
    protected $table = "vendors";

    public $timestamps = false;

    public function items()
    {
        return $this->hasMany(ItemMaster::class, 'vendors_id', 'id');
    }
}
