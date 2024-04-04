<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\Privilege;
use App\SubscriptionPackage;
use Auth;

class SubscriptionPackageController extends Controller
{
    public function index(){
    	if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $subscription_packages = SubscriptionPackage::with('role')->get();
    	return view('subscription_package.list', compact('subscription_packages'));
    }
    public function edit($id){
    	if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $subscriptionPackage = SubscriptionPackage::Where('id',$id)->first();
    	if($subscriptionPackage) {
            //$roles = Privilege::whereIn('id',[2,3,4])->get();
            return view('subscription_package.edit',compact('subscriptionPackage'));
        }
        return redirect('/subscriptionPackages')->with('error','Subscription Packages not found');
    }
    public function update(Request $request){
    	if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $subscriptionPackage = SubscriptionPackage::Where('id',$request->id)->first();
        if($subscriptionPackage) {
            $subscriptionPackage->name = $request->name;
            $subscriptionPackage->amount = $request->amount;
            $subscriptionPackage->month = $request->month;
        	if($subscriptionPackage->save()) {
               return redirect('/subscriptionPackages')->with('success','Subscription Package updated successful');
            }
        	else
               return redirect('/subscriptionPackages')->with('error','Subscription Package update failed');
        }
        return redirect('/subscriptionPackages')->with('error','Subscription Packages not found');
    }
    public function apiList(){
        $user = auth()->guard('api')->user();
        $subscriptionPackages = SubscriptionPackage::where('role_id',$user->privileges)->get();
        if(count($subscriptionPackages)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $subscriptionPackages,
                'message' => 'Get subscription packages successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get subscription packages failed'
        ]);
    }
}
