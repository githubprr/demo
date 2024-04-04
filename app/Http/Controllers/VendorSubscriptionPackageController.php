<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\VendorSubscriptionFeature;
use App\VendorSubFeatureMaster;
use App\VendorSubscriptionPkg;
use Auth;
use File;
use Razorpay\Api\Api;
use Session;
use Exception;
use Config;

class VendorSubscriptionPackageController extends Controller
{
    public function index(){
    	$vendor_subscription_pkgs = VendorSubscriptionPkg::get();
    	return view('vendor_subscription_package.list', compact('vendor_subscription_pkgs'));
    }
    public function add(){
        $vendor_subs_feature_master = VendorSubFeatureMaster::get();
        return view('vendor_subscription_package.add', compact('vendor_subs_feature_master'));
    }
    public function save(Request $request){
        $vendorSubscriptionPkg = new VendorSubscriptionPkg;
        $vendorSubscriptionPkg->name = $request->name;
        $vendorSubscriptionPkg->amount = $request->amount;
        $vendorSubscriptionPkg->month = $request->month;
        // if($vendorSubscriptionPkg->save()) {
        if(2>1) {
            // $vendor_subs_feature_master = VendorSubFeatureMaster::get();
            // foreach ($vendor_subs_feature_master as $key => $feature) {
            //     $feature_slug = $feature->slug;
            //     $vendorSubscriptionFeature = new VendorSubscriptionFeature;
            //     $vendorSubscriptionFeature->vendor_subscription_pkg_id = $vendorSubscriptionPkg->id;
            //     $vendorSubscriptionFeature->slug = $feature_slug;
            //     $vendorSubscriptionFeature->name = $feature->name;
            //     $vendorSubscriptionFeature->type = $feature->type;
            //     if($feature->type=="int") {
            //         $vendorSubscriptionFeature->value = ($request->has($feature_slug.'_value') && $request->input($feature_slug.'_value')!="")?$request->input($feature_slug.'_value'):'';
            //         $vendorSubscriptionFeature->text = ($request->has($feature_slug.'_text') && $request->input($feature_slug.'_text')!="")?$request->input($feature_slug.'_text'):'x';
            //     }
            //     else {
            //         $vendorSubscriptionFeature->value = ($request->has($feature_slug.'_use'))?1:0;
            //         $vendorSubscriptionFeature->text = ($request->has($feature_slug.'_text') && $request->input($feature_slug.'_text')!="")?$request->input($feature_slug.'_text'):'x';
            //     }
            //     $vendorSubscriptionFeature->is_use = ($request->has($feature_slug.'_use'))?1:0;
            //     $vendorSubscriptionFeature->save();
            // }
            $api = new Api(Config::get('app.rzp_key'), Config::get('app.rzp_secret'));
        
            // $plan = $api->plan->create(
            //     array(
            //         'period' => 'monthly',
            //         'interval' => $request->month,
            //         'item' => array(
            //             'name' => $request->name,
            //             'description' => $request->name,
            //             'amount' => $request->amount*100,
            //             'currency' => 'INR')
            //     )
            // );
            // logger()->info('plan=');
            // logger()->info($plan->toArray());
            $plan = "plan_LaUM9Vit4rwAjt";
            if($plan) {
                // $subscription = $api->subscription->create(
                //     array(
                //         'plan_id' => $plan->id,
                //         'customer_notify' => 1,
                //         'quantity'=>5,
                //         'total_count' => 6,
                //         'start_at' => 1495995837,
                //         'addons' => array(
                //             array(
                //                 'item' => array(
                //                     'name' => 'Delivery charges',
                //                     'amount' => 30000,
                //                     'currency' => 'INR')
                //             )
                //         ),
                //         'notes'=> array('key1'=> 'value3','key2'=> 'value2')
                //     )
                // );

                $subscription = $api->subscription->create(
                    array(
                        'plan_id' => $plan,
                        'total_count' => 12,
                        'quantity' => 1,
                        // 'expire_by' => 1633237807,
                        'customer_notify' => 0
                        // 'addons' => array(
                        //     array(
                        //         'item'=>array(
                        //             'name' => 'Delivery charges',
                        //             'amount' => 30000,
                        //             'currency' => 'INR')
                        //     )
                        // ),
                        // 'notes'=>array(
                        //     'notes_key_1'=>'Tea, Earl Grey, Hot',
                        //     'notes_key_2'=>'Tea, Earl Grey… decaf.'
                        // ),
                        // 'notify_info'=>array(
                        //     'notify_phone' => '9123456789',
                        //     'notify_email'=> 'gaurav.kumar@example.com'
                        // )
                    )
                );
                dd($subscription);
            }
           return redirect('/vendorSubscriptionPackage')->with('success','Vendor subscription package added successful');
        }
    	else
           return redirect('/vendorSubscriptionPackage')->with('error','Vendor subscription package add failed');
    }
    public function edit($id){
    	$vendorSubscriptionPkg = VendorSubscriptionPkg::with('vendor_subscription_feature')->Where('id',$id)->first();
        $vendor_subs_feature_master = VendorSubFeatureMaster::get();
        return view('vendor_subscription_package.edit',compact('vendorSubscriptionPkg','vendor_subs_feature_master'));
    }
    public function update(Request $request){
    	$vendorSubscriptionPkg = VendorSubscriptionPkg::Where('id',$request->id)->first();
    	$vendorSubscriptionPkg->name = $request->name;
        $vendorSubscriptionPkg->amount = $request->amount;
        $vendorSubscriptionPkg->month = $request->month;
    	if($vendorSubscriptionPkg->save()) {
            $vendor_subs_feature_master = VendorSubFeatureMaster::get();
            $vendor_subs_feature = VendorSubscriptionFeature::where('vendor_subscription_pkg_id',$request->id)->get();
            foreach ($vendor_subs_feature_master as $key => $feature) {
                $feature_slug = $feature->slug;
                $vendorSubscriptionFeature = $vendor_subs_feature->where('slug',$feature_slug)->first();
                if(!$vendorSubscriptionFeature)
                    $vendor_subs_feature = new VendorSubscriptionFeature;
                $vendorSubscriptionFeature->vendor_subscription_pkg_id = $vendorSubscriptionPkg->id;
                $vendorSubscriptionFeature->slug = $feature_slug;
                $vendorSubscriptionFeature->name = $feature->name;
                $vendorSubscriptionFeature->type = $feature->type;
                if($feature->type=="int") {
                    $vendorSubscriptionFeature->value = ($request->has($feature_slug.'_value') && $request->input($feature_slug.'_value')!="")?$request->input($feature_slug.'_value'):'';
                    $vendorSubscriptionFeature->text = ($request->has($feature_slug.'_text') && $request->input($feature_slug.'_text')!="")?$request->input($feature_slug.'_text'):'x';
                }
                else {
                    $vendorSubscriptionFeature->value = ($request->has($feature_slug.'_use'))?1:0;
                    $vendorSubscriptionFeature->text = ($request->has($feature_slug.'_text') && $request->input($feature_slug.'_text')!="")?$request->input($feature_slug.'_text'):'x';
                }
                $vendorSubscriptionFeature->is_use = ($request->has($feature_slug.'_use'))?1:0;
                $vendorSubscriptionFeature->save();
            }
           return redirect('/vendorSubscriptionPackage')->with('success','Category updated successful');
        }
    	else
           return redirect('/vendorSubscriptionPackage')->with('error','Category update failed');
    }
    public function delete($id){
    	$category = CategoryMaster::Where('id',$id)->first();
    	if($category->delete())
           return redirect('/vendorSubscriptionPackage')->with('success','Category deleted successful');
    	else
           return redirect('/vendorSubscriptionPackage')->with('error','Category delete failed');
    }
}
