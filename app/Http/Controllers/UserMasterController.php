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
// use App\CompanyMaster;
use Auth;
use File;
use Hash;
use Validator;
use Config;

class UserMasterController extends Controller
{
    public function index(){
        if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        // $users = User::where('id','<>',1)->withCount('requisitions')->withCount('enquiries')->withCount('purchase_orders')->withCount('sales_orders')->withCount('shipments')->get();
        $users = User::where('id','<>',1)->whereNotIn('privileges',[3,2,6,7,8])->where('verified',1)->withCount('requisitions')->withCount('enquiries')->orderBy('id','desc')->get();
        return view('user_master.list', compact('users'));
    }
    public function add(){
        if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $countries = CountryMaster::all();
        $privileges = Privilege::where('is_company_user',0)->whereNotIn('id',[1,2,3])->get();
        // $companies = CompanyMaster::all();
        $companies = User::where('privileges',2)->get();
        return view('user_master.add', compact('countries','privileges','companies'));
    }
    public function save(Request $request){
        if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|integer',
            'state_id' => 'required|integer',
            'district_id' => 'required|integer',
            'taluka_id' => 'required|integer',
            'village_id' => 'required|integer'
        ], [
            'country_id.required' => 'Country is required',
            'state_id.required' => 'State is required',
            'district_id.required' => 'District is required',
            'taluka_id.required' => 'Taluka is required',
            'village_id.required' => 'Village is required'
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
            $user->privileges = $request->privileges;
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
               return redirect('/userMaster')->with('success','User added successful');
            }
            else
               return redirect('/userMaster')->with('error','User add failed');
        }
    }
    public function edit($id){
        if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $user = User::Where('id',$id)->first();
        if($user) {
            $countries = CountryMaster::all();
            $states = StateMaster::where('country_id',$user->country_id)->get();
            $districts = DistrictMaster::where('state_id',$user->state_id)->get();
            $talukas = TalukaMaster::where('district_id',$user->district_id)->get();
            $villages = VillageMaster::where('taluka_id',$user->taluka_id)->get();
            $privileges = Privilege::where('is_company_user',0)->whereNotIn('id',[1,2,3])->get();
            // $companies = CompanyMaster::all();
            $companies = User::where('privileges',2)->get();
            return view('user_master.edit',compact('user', 'countries', 'states', 'districts', 'talukas', 'villages', 'privileges', 'companies'));
        }
        return redirect('/userMaster')->with('error','User not found');
    }
    public function update(Request $request){
        if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|integer',
            'state_id' => 'required|integer',
            'district_id' => 'required|integer',
            'taluka_id' => 'required|integer',
            'village_id' => 'required|integer'
        ], [
            'country_id.required' => 'Country is required',
            'state_id.required' => 'State is required',
            'district_id.required' => 'District is required',
            'taluka_id.required' => 'Taluka is required',
            'village_id.required' => 'Village is required'
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
            $user->privileges = $request->privileges;
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
            if($user->save())
               return redirect('/userMaster')->with('success','User updated successful');
            else
               return redirect('/userMaster')->with('error','User update failed');
        }
        return redirect('/userMaster')->with('error','User not found');
    }
    public function delete($id){
    	if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if($id==1 || $id==2 || $id==3)
           return redirect('/userMaster')->with('error','Can not delete this user');

        $user = User::Where('id',$id)->first();
        if($user) {
            if($user->delete())
               return redirect('/userMaster')->with('success','User deleted successful');
            else
               return redirect('/userMaster')->with('error','User delete failed');
        }
        return redirect('/userMaster')->with('error','User not found');
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
    public function unverifiedList(){
        if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $users = User::where('verified',0)->orderBy('id','desc')->get();
        return view('user_master.unverified_list', compact('users'));
    }
    public function approveUser($id){
        $user = User::where('id',$id)->first();
        $user->verified = 1;
        if($user->save())
            return redirect('/userUnverifiedList')->with('success','User approved successful');
        else
           return redirect('/userUnverifiedList')->with('error','User approve failed');
    }
    public function profile(){
        $user = User::where('id',Auth::user()->id)->first();
        $countries = CountryMaster::all();
        $states = StateMaster::where('country_id',$user->country_id)->get();
        $districts = DistrictMaster::where('state_id',$user->state_id)->get();
        $talukas = TalukaMaster::where('district_id',$user->district_id)->get();
        $villages = VillageMaster::where('taluka_id',$user->taluka_id)->get();
        if(Auth::user()->privileges==3) {
            $google_map_key = Config::get('app.google_map_key');        
            return view('vendor_master.profile',compact('user', 'countries', 'states', 'districts', 'talukas', 'villages', 'google_map_key'));
        }
        else
            return view('user_master.profile',compact('user', 'countries', 'states', 'districts', 'talukas', 'villages'));
    }
    public function updateProfile(Request $request){
        $user = User::where('id',Auth::user()->id)->first();
        if($user->privileges==2) {
            $validator = Validator::make($request->all(), [
                'country_id' => 'required|integer',
                'state_id' => 'required|integer'
            ], [
                'country_id.required' => 'Country is required',
                'state_id.required' => 'State is required'
            ]);
        }
        else if($user->privileges==3) {
            $validator = Validator::make($request->all(), [
                'mobile' => 'required',
                'address' => 'required',
                'country_id' => 'required|integer',
                'state_id' => 'required|integer'
            ], [
                'mobile.required' => 'Mobile is required',
                'address.required' => 'Address is required',
                'country_id.required' => 'Country is required',
                'state_id.required' => 'State is required'
            ]);
        }
        else {
            $validator = Validator::make($request->all(), [
                'country_id' => 'required|integer',
                'state_id' => 'required|integer',
                'district_id' => 'required|integer',
                'taluka_id' => 'required|integer',
                'village_id' => 'required|integer'
            ], [
                'country_id.required' => 'Country is required',
                'state_id.required' => 'State is required',
                'district_id.required' => 'District is required',
                'taluka_id.required' => 'Taluka is required',
                'village_id.required' => 'Village is required'
            ]);
        }
        if($validator->fails())
            return back()->with('validations',$validator->errors());

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
        $user->district_id = null;
        $user->taluka_id = null;
        $user->village_id = null;
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
           return back()->with('success','Profile updated successful');
        else
           return back()->with('error','Profile update failed');
    }
    public function changePassword(Request $request)
    {
        $u_id = Auth::user()->id;
        $user_data = User::where('id', $u_id)->first();
        
        $old_password = $request->oldpassword;
        $new_password = $request->password;
        
        if (Hash::check($old_password, $user_data->password))
        {
            $password = $request->password;
            $confirm_password = $request->confirm_password;
            if($password!=$confirm_password)
               return back()->with('error','Password and Confirm Password does not match');

            $user_data->password = bcrypt($new_password);
            if($user_data->save())
            {
                $msg = "Password Changed Successfully";
                return back()->with('success', $msg);
            }
            else
            {
                $msg = "Password Change Failed";
                return back()->with('error', $msg);
            }
        }
        else
        {
            $msg = "Old Password Does Not Match";
            return back()->with('error', $msg);
        }
    }
    public function getUsersByPrivilege($id){
        $users = User::select('id','name')->Where('privileges',$id)->orderBy('name','asc')->get();
        return response()->json($users);
    }
    public function badgeUnverifiedList(){
        if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $users = User::where('trusted_badge',2)->orWhere('verified_badge',2)->orderBy('id','desc')->get();
        return view('user_master.badge_unverified_list', compact('users'));
    }
    public function approveUserBadge($id){
        $user = User::where('id',$id)->first();
        if($user->verified_badge==2)
            $user->verified_badge = 1;
        if($user->trusted_badge==2)
            $user->trusted_badge = 1;
        if($user->save())
            return redirect('/badgeUnverifiedList')->with('success','User badge approved successful');
        else
           return redirect('/badgeUnverifiedList')->with('error','User badge approve failed');
    }
}
