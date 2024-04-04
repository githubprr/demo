<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyMaster extends Model
{
    protected $table = "companies";

    public $timestamps = false;

    public function items()
    {
        return $this->hasMany(ItemMaster::class, 'companies_id', 'id');
    }
}
