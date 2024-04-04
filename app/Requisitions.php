<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class Requisitions extends Model
{
    use SoftDeletes;
    use Loggable;
    
    protected $table = 'requisitions';
	
	 /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'remarks',
		'required_on',
		'user_id',
		'reference_requisition_no',
        'attachment',
        'lat',
		'lng',
        'category_group_id',
        'group_id'
    ];
	
	/**
     * Get the item associated with the requistion.
     */
    public function requisition_items()
    {
        return $this->hasMany(RequisitionItems::class, 'requisition_id', 'id');
    }
	
	/**
     * Get the user associated with the requistion.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')->withTrashed();
    }

    public function enquries()
    {
        return $this->hasMany(Enquiries::class, 'requisition_id', 'id');
    }
}