<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class PrivilegeRoles extends Model
{
    use Loggable;
    
    protected $table = "privilege_roles";

    protected $fillable = [
        'privilege_id',
        'module_id',
        'is_visible',
        'is_create',            
        'is_read',
        'is_edit',
        'is_delete'
    ];

    public function module() {
        return $this->hasOne(Module::class, 'id', 'module_id');
    }
}
