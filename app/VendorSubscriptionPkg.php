<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorSubscriptionPkg extends Model
{
    protected $table = "vendor_subscription_pkg";

    public function vendor_subscription_feature()
    {
        return $this->hasMany(VendorSubscriptionFeature::class, 'vendor_subscription_pkg_id', 'id');
    }

 //    public function getIDAttribute()
	// {
	// 	$salt="MY_SECRET_STUFF";
	// 	$encrypted_id = base64_encode($this->attributes['id'] . $salt);
	//     return $encrypted_id;
	// }
	// public function setIDAttribute($value)
 //    {
 //    	$salt="MY_SECRET_STUFF";
	// 	$decrypted_id_raw = base64_decode($value);
	// 	$decrypted_id = preg_replace(sprintf('/%s/', $salt), '', $decrypted_id_raw);
 //        $this->attributes['id'] = strtolower($decrypted_id);
 //    }
}
