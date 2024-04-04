<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\CountryMaster;
use App\StateMaster;
use App\DistrictMaster;
use App\TalukaMaster;
use App\VillageMaster;
use App\Privilege;
use App\PointMaster;
use App\VendorReview;
use App\Subscription;
use App\SpecialAddon;
use App\VendorItem;
use App\VendorRequisition;
use Auth;
use File;
use Hash;
use Validator;
use Config;
use DB;

class VendorMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('vendors','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $vendors = User::where('privileges',3)->get();
    	return view('vendor_master.list', compact('vendors'));
    }
    public function add(){
        if(!$this->getPermission('vendors','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $countries = CountryMaster::all();
        $privileges = Privilege::where('is_company_user',0)->get();
        $companies = User::where('privileges',2)->get();
        $google_map_key = Config::get('app.google_map_key');
        return view('vendor_master.add', compact('countries','privileges','companies','google_map_key'));
    }
    public function save(Request $request){
        if(!$this->getPermission('vendors','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'address' => 'required',
            'country_id' => 'required|integer',
            'state_id' => 'required|integer',
            'district_id' => 'required|integer',
            'taluka_id' => 'required|integer',
            'village_id' => 'required|integer',
            'lat' => 'required',
            'lng' => 'required'
        ], [
            'mobile.required' => 'Mobile is required',
            'address.required' => 'Address is required',
            'country_id.required' => 'Country is required',
            'state_id.required' => 'State is required',
            'district_id.required' => 'District is required',
            'taluka_id.required' => 'Taluka is required',
            'village_id.required' => 'Village is required',
            'lat.required' => 'Latitude is required',
            'lng.required' => 'Longitude is required'
        ]);
        if($validator->fails())
            return back()->with('validations',$validator->errors());

        $password = $request->password;
        $password_confirmation = $request->password_confirmation;
        if($password!=$password_confirmation)
           return back()->with('error','Password and Confirm Password does not match');

        $is_email = User::Where('email',$request->email)->count();
        if($is_email>0)
           return back()->with('error','Email ID already taken');

        $ref_user = null;
        if(isset($request->referral_code)) {
            $ref_user = User::where('referral_code',$request->referral_code)->first();
            if(!$ref_user)
                return back()->withInput()->with('error','Invalid referral code');
        }

        if($request->hasFile('photo')){
            $file= $_FILES['photo']['name'];
            $var=explode(".",$file);
            $ext='.'.end($var);
            $user_id = Auth::user()->id;
            $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
            $filename =  $value.'-'.$user_id .$ext;
            $filepath = public_path('uploads/user/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['photo']['tmp_name'], $filepath.$filename);

            $user = new User;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->privileges = 3;
            $user->photo = $filename;
            if(isset($request->company_id))
                $user->company_id = $request->company_id;
            if(isset($request->designation))
                $user->designation = $request->designation;
            if(isset($request->mobile))
                $user->mobile = $request->mobile;
            if(isset($request->total_experience))
                $user->total_experience = $request->total_experience;
            if(isset($request->experience_in))
                $user->experience_in = $request->experience_in;
            if(isset($request->address))
                $user->address = $request->address;
            if(isset($request->country_id))
                $user->country_id = $request->country_id;
            if(isset($request->state_id))
                $user->state_id = $request->state_id;
            if(isset($request->district_id))
                $user->district_id = $request->district_id;
            if(isset($request->taluka_id))
                $user->taluka_id = $request->taluka_id;
            if(isset($request->village_id))
                $user->village_id = $request->village_id;
            if(isset($request->lat))
                $user->lat = $request->lat;
            if(isset($request->lng))
                $user->lng = $request->lng;
            
            $wallet_points = 0;
            $points_master = PointMaster::where('slug','app_register')->first();;
            if($points_master)
                $wallet_points = $points_master->point;

            $user->wallet_points = $wallet_points;
            
            $self_referral_code = $this->generateReferralCode(6);
            $user->referral_code = strtolower($request->name[0]).strtolower($request->name[1]).'-'.$self_referral_code;
            if(isset($request->referral_code) && $ref_user)
                $user->referred_by = $ref_user->id;

            $verified = 1;
            if($request->privileges==2 || $request->privileges==3)
                $verified = 0;
            $user->verified = $verified;

            if($user->save()) {
                if(isset($request->referral_code) && $ref_user) {
                    if($points_master) {
                        $ref_wallet_points = $points_master->app_share;
                        if($ref_user->wallet_points)
                            $ref_user->wallet_points += $ref_wallet_points;
                        else
                            $ref_user->wallet_points = $ref_wallet_points;
                        $ref_user->save();
                    }
                }
               return redirect('/vendorMaster')->with('success','Vendor added successful');
            }
            else
               return redirect('/vendorMaster')->with('error','Vendor add failed');
        }
    }
    public function edit($id){
        if(!$this->getPermission('vendors','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $user = User::Where('id',$id)->first();
        if($user) {
            $countries = CountryMaster::all();
            $states = StateMaster::where('country_id',$user->country_id)->get();
            $districts = DistrictMaster::where('state_id',$user->state_id)->get();
            $talukas = TalukaMaster::where('district_id',$user->district_id)->get();
            $villages = VillageMaster::where('taluka_id',$user->taluka_id)->get();
            $privileges = Privilege::where('is_company_user',0)->get();
            // $companies = CompanyMaster::all();
            $companies = User::where('privileges',2)->get();
            $google_map_key = Config::get('app.google_map_key');
            return view('vendor_master.edit',compact('user', 'countries', 'states', 'districts', 'talukas', 'villages', 'privileges', 'companies', 'google_map_key'));
        }
        return redirect('/vendorMaster')->with('error','Vendor not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('vendors','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'address' => 'required',
            'country_id' => 'required|integer',
            'state_id' => 'required|integer',
            'district_id' => 'required|integer',
            'taluka_id' => 'required|integer',
            'village_id' => 'required|integer',
            'lat' => 'required',
            'lng' => 'required'
        ], [
            'mobile.required' => 'Mobile is required',
            'address.required' => 'Address is required',
            'country_id.required' => 'Country is required',
            'state_id.required' => 'State is required',
            'district_id.required' => 'District is required',
            'taluka_id.required' => 'Taluka is required',
            'village_id.required' => 'Village is required',
            'lat.required' => 'Latitude is required',
            'lng.required' => 'Longitude is required'
        ]);
        if($validator->fails())
            return back()->with('validations',$validator->errors());

        $user = User::Where('id',$request->id)->first();
        if($user) {
            if($request->hasFile('photo')){
                $image_path = public_path() . '/uploads/user/'.$user->photo;
                if(File::exists($image_path)) {
                    File::delete($image_path);
                }
                $file= $_FILES['photo']['name'];
                $var=explode(".",$file);
                $ext='.'.end($var);
                $user_id = Auth::user()->id;
                $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename =  $value.'-'.$user_id .$ext;
                $filepath = public_path('uploads/user/');

                if(!File::isDirectory($filepath))
                    File::makeDirectory($filepath, 0777, true, true);

                move_uploaded_file($_FILES['photo']['tmp_name'], $filepath.$filename);
                $user->photo = $filename;
            }
            $user->name = $request->name;
            if(isset($request->company_name))
                $user->company_name = $request->company_name;
            if(isset($request->designation))
                $user->designation = $request->designation;
            if(isset($request->mobile))
                $user->mobile = $request->mobile;
            if(isset($request->total_experience))
                $user->total_experience = $request->total_experience;
            if(isset($request->experience_in))
                $user->experience_in = $request->experience_in;
            if(isset($request->address))
                $user->address = $request->address;
            if(isset($request->country_id))
                $user->country_id = $request->country_id;
            if(isset($request->state_id))
                $user->state_id = $request->state_id;
            if(isset($request->district_id))
                $user->district_id = $request->district_id;
            if(isset($request->taluka_id))
                $user->taluka_id = $request->taluka_id;
            if(isset($request->village_id))
                $user->village_id = $request->village_id;
            if(isset($request->lat))
                $user->lat = $request->lat;
            if(isset($request->lng))
                $user->lng = $request->lng;
            
            if($user->save())
               return redirect('/vendorMaster')->with('success','Vendor updated successful');
            else
               return redirect('/vendorMaster')->with('error','Vendor update failed');
        }
        return redirect('/vendorMaster')->with('error','Vendor not found');
    }
    public function delete($id){
    	if(!$this->getPermission('vendors','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	if($id==1 || $id==2 || $id==3)
           return redirect('/vendorMaster')->with('error','Can not delete this user');

        $user = User::Where('id',$id)->first();
        if($user) {
            if($user->delete())
               return redirect('/vendorMaster')->with('success','Vendor deleted successful');
            else
               return redirect('/vendorMaster')->with('error','Vendor delete failed');
        }
        return redirect('/vendorMaster')->with('error','Vendor not found');
    }
    function generateReferralCode($length = 6) {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        $isRefferalCode = User::where('referral_code', $randomString)->count();
        if($isRefferalCode==0)
        {
            return $randomString;
        }
        else
        {
            $this->generateReferralCode(6);
        }
    }
    public function apiSaveReview(Request $request){
        $user = auth()->guard('api')->user();
        $vendor_review_exists = VendorReview::where('vendor_id',$request->vendor_id)->where('user_id',$user->id)->exists();
        if(!$vendor_review_exists) {
            $vendor_review = new VendorReview;
            $vendor_review->vendor_id = $request->vendor_id;
            $vendor_review->user_id = $user->id;
            $vendor_review->rating = $request->rating;
            if(isset($request->review))
                $vendor_review->review = urldecode($request->review);
            $vendor_review->save();
            if($vendor_review) {
                return response()->json([
                    'errorCode' => 0,
                    'message' => 'Vendor review added successful'
                ]);
            }

            return response()->json([
                'errorCode' => 1,
                'message' => 'Vendor review add failed'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'You have already given vendor review'
        ]);
    }
    public function apiGetVendorDetails($id){
        $user = auth()->guard('api')->user();
        $vendor = User::
            select('id','name','email','mobile','photo',DB::raw('(SELECT avg(rating) FROM vendor_reviews WHERE vendor_reviews.vendor_id=users.id) AS avg_rating'))
            ->where('id',$id)
            ->first();
        if($vendor) {
            if($vendor->avg_rating==null)
                $vendor->avg_rating = 0;
            else
                $vendor->avg_rating = round($vendor->avg_rating,2);
            
            $rating_array = array();
            for ($i=0; $i < 5; $i++) { 
                $new_rating_group['rating'] = $i+1;
                $new_rating_group['total'] = 0;
                array_push($rating_array, $new_rating_group);
            }
            $rating_group = VendorReview::where('vendor_id',$id)->select('rating', DB::raw('count(rating) as total'))->groupBy('rating')->orderBy('rating')->get();
            if(count($rating_group)) {
                foreach ($rating_group as $key => $value) {
                    $rating_array[$value->rating-1]['total'] = $value->total;
                }
            }
            $vendor->rating_group = $rating_array;
            
            // $subscription_array = array();            
            $subscription_product_nos = 0;
            // array_push($subscription_array, $subscription_item1);
            $subscription_requisition_nos = 0;
            // array_push($subscription_array, $subscription_item2);
            $special_addon_product_nos = 0;
            // array_push($subscription_array, $special_addon_item1);
            $special_addon_requisition_nos = 0;
            // array_push($subscription_array, $special_addon_item2);
            $used_product_nos = 0;
            // array_push($subscription_array, $used_item1);
            $used_requisition_nos = 0;
            // array_push($subscription_array, $used_item2);
            
            $subscription = Subscription::where('user_id',$id)->first();
            if($subscription) {
                // $subscription_array[0]['subscription_product_nos'] = (int)$subscription->product_nos;
                // $subscription_array[1]['subscription_requisition_nos'] = (int)$subscription->requisition_nos;
                $subscription_product_nos = (int)$subscription->product_nos;
                $subscription_requisition_nos = (int)$subscription->requisition_nos;
            }
            // $subscription_array[2]['special_addon_product_nos'] = SpecialAddon::where('user_id',$id)->where('special_addon_package_id',1)->count('qty');
            // $subscription_array[3]['special_addon_requisition_nos'] = SpecialAddon::where('user_id',$id)->where('special_addon_package_id',2)->count('qty');
            $special_addon_product_nos = SpecialAddon::where('user_id',$id)->where('special_addon_package_id',1)->count('qty');
            $special_addon_requisition_nos = SpecialAddon::where('user_id',$id)->where('special_addon_package_id',2)->count('qty');
            
            // $subscription_array[4]['used_product_nos'] = VendorItem::where('user_id',$id)->count('id');
            // $subscription_array[5]['used_requisition_nos'] = VendorRequisition::where('user_id',$id)->count('id');
            $used_product_nos = VendorItem::where('user_id',$id)->count('id');
            $used_requisition_nos = VendorRequisition::where('user_id',$id)->count('id');
            
            // $vendor->subscription_group = $subscription_array;
            $vendor->subscription_product_nos = $subscription_product_nos;
            $vendor->subscription_requisition_nos = $subscription_requisition_nos;
            $vendor->special_addon_product_nos = $special_addon_product_nos;
            $vendor->special_addon_requisition_nos = $special_addon_requisition_nos;
            $vendor->used_product_nos = $used_product_nos;
            $vendor->used_requisition_nos = $used_requisition_nos;
            
            $vendor->my_rating = VendorReview::where('vendor_id',$id)->where('user_id',$user->id)->select('rating','review')->first();
            return response()->json([
                'errorCode' => 0,
                'data' => $vendor,
                'message' => 'Get vendor details successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get vendor details failed'
        ]);
    }
}
