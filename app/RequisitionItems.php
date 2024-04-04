<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class RequisitionItems extends Model
{
    use Loggable;
    
    protected $table = 'requisition_items';
	
	 /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_master_id',
        'item_name',
		'item_description',
		'item_best_price',
		'qty',
        'requisition_id',
        'uom_id',
        'wt_size',
		'size_unit_text'
    ];
	
    public function item()
    {
        // return $this->belongsTo(ItemMaster::class, 'id', 'item_master_id');
        return $this->belongsTo(ItemMaster::class, 'item_master_id', 'id')->withTrashed();
    }

    public function uom()
    {
        // return $this->belongsTo(ItemMaster::class, 'id', 'item_master_id');
        return $this->belongsTo(UomMaster::class, 'uom_id', 'id')->withTrashed();
    }
}