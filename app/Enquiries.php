<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class Enquiries extends Model
{
    use SoftDeletes;
    use Loggable;
    
    protected $table = 'enquiries';
	
	 /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [         
		'reference_enquiry_no',
		'requisition_id',
		'vendor_name',
		'vendor_address',
		'vendor_contact',
		'vendor_email',
		'user_id',
		'price',
		'note',
        'behalf_user_id'
    ];
	
	/**
     * Get the item associated with the requistion.
     */
    public function requisition()
    {
        return $this->hasOne(Requisitions::class, 'id', 'requisition_id')->withTrashed();
    }
	
	/**
     * Get the item associated with the requistion.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')->withTrashed();
    }

    public function group()
    {
        return $this->hasOne(Group::class, 'id', 'group_id')->withTrashed();
    }

    public function purchase_orders()
    {
        return $this->hasMany(PurchaseOrder::class, 'enquiry_id', 'id');
    }
}