<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Coupon;
use Auth;
use File;
use Validator;
use Session;

class CouponController extends Controller
{
    public function index(){
        if(!$this->getPermission('coupons','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $coupons = Coupon::with('user')->orderBy('id','desc')->get();
    	return view('coupon.list', compact('coupons'));
    }
    public function add(){
        if(!$this->getPermission('coupons','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        return view('coupon.add');
    }
    // public function save(Request $request){
    //     $country = new Coupon;
    //     $country->name = $request->name;
    //     if($country->save())
    //        return redirect('/coupons')->with('success','Coupon added successful');
    // 	else
    //        return redirect('/coupons')->with('error','Coupon add failed');
    // }
    public function save(Request $request){
        if(!$this->getPermission('coupons','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $input = $request->all();
        $request->validate([
            'discount' => 'required',
            'expires_at' => 'required'
        ]);
        // $validator = Validator::make($input, [
        //     'name' => 'required|alpha'
        // ]);
        // if($validator->fails()){
        //     // dd($validator->errors());
        //     return back()->with('error',$validator->errors());       
        // }
        // dd(1);
        $country = new Coupon;
        $country->coupon_code = $this->generateCouponCode(10);;
        $country->discount = $request->discount;
        $country->expires_at = date('Y-m-d', strtotime($request->expires_at));
        $country->status = 0;
        if($country->save())
           return redirect('/coupons')->with('success','Coupon added successful');
        else
           return redirect('/coupons')->with('failed','Coupon add failed');
    }
    public function edit($id){
        if(!$this->getPermission('coupons','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$country = Coupon::Where('id',$id)->first();
        return view('coupon.edit',compact('country'));
    }
    public function update(Request $request){
        if(!$this->getPermission('coupons','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$country = Coupon::Where('id',$request->id)->first();
    	$country->name = $request->name;
        $country->name_hn = $request->name_hn;
        $country->name_mr = $request->name_mr;
    	if($country->save())
           return redirect('/coupons')->with('success','Coupon updated successful');
    	else
           return redirect('/coupons')->with('error','Coupon update failed');
    }
    public function delete($id){
    	if(!$this->getPermission('coupons','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$country = Coupon::Where('id',$id)->first();
    	if($country->delete())
           return redirect('/coupons')->with('success','Coupon deleted successful');
    	else
           return redirect('/coupons')->with('error','Coupon delete failed');
    }

    function generateCouponCode($length = 10) {
        // $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        $isRefferalCode = Coupon::where('coupon_code', $randomString)->count();
        if($isRefferalCode==0)
        {
            return $randomString;
        }
        else
        {
            $this->generateCouponCode(10);
        }
    }
    public function apply(Request $request){
        $coupon = Coupon::Where('coupon_code',$request->coupon_code)->first();
        if(isset($coupon)) {
            if($coupon->status==1 || $coupon->status==2)
                return back()->with('error','Coupon already used');
            else if($coupon->expires_at<date('Y-m-d'))
                return back()->with('error','Coupon expired');
            
            Session::put('coupon_apply', $coupon);
            $coupon->status = 2;
            $coupon->user_id = Auth::user()->id;
            $coupon->save();
            return back()->with('success','Coupon apply successful');
        }
        return back()->with('error','Invalid coupon code');
    }
    public function cancel(Request $request){
        if(Session::has('coupon_apply')) {
            $coupon_apply = Session::get('coupon_apply');
            $coupon = Coupon::Where('coupon_code',$coupon_apply->coupon_code)->first();
            if(isset($coupon) && $coupon->status==2 && $coupon->user_id==Auth::user()->id) {
                $coupon->status = 0;
                $coupon->user_id = null;
                $coupon->save();
                Session::forget('coupon_apply');
                return back()->with('success','Coupon cancel successful');
            }
        }
        return back()->with('error','Something went wrong');
    }
}
