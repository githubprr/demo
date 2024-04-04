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
use App\CompanyUserArea;
use Auth;
use File;
use Hash;
use Validator;

class CompanyUserMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('company_users','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        // $users = User::where('id','<>',1)->withCount('requisitions')->withCount('enquiries')->withCount('purchase_orders')->withCount('sales_orders')->withCount('shipments')->get();
        // $users = User::where('added_by',Auth::user()->id)->orderBy('id','desc')->get();
        $users = User::where('added_by',Auth::user()->id)->orderBy('id','desc')->get();
        return view('company_user_master.list', compact('users'));
    }
    public function add(){
        if(!$this->getPermission('company_users','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $state_allow = false;
        $district_allow = false;
        $taluka_allow = false;
        if(Auth::user()->privileges==2) {
            $subscription_value = $this->getSubscriptionDetails('employee_nos');
            if($subscription_value==null)
                return redirect('/companyUserMaster')->with('error','You dont have any subscription to add company users');
            else if($subscription_value==0)
                return redirect('/companyUserMaster')->with('error','You dont permission to add company users');
            if($subscription_value!="-1") {
                $companyUsers = User::where('added_by',Auth::user()->id)->get();
                if(sizeof($companyUsers)>=0) {
                    $subscription_row = $this->getSubscriptionDetailsRow();
                    if($subscription_row) {
                        if(sizeof($companyUsers) >= $subscription_row->employee_nos)
                            return redirect('/companyUserMaster')->with('error','Your subscription allowed you to add only '.$subscription_row->employee_nos.' employees');
                        
                        $companyUsersStateAccountCount = $companyUsers->where('management_level',1)->count();
                        if($subscription_row->state_account_nos=="-1" || ($companyUsersStateAccountCount<$subscription_row->state_account_nos))
                            $state_allow = true;
                        if($subscription_row->district_allow==1)
                            $district_allow = true;
                        if($subscription_row->taluka_allow==1)
                            $taluka_allow = true;
                    }
                    else {
                        return redirect('/companyUserMaster')->with('error','You dont have any subscription to add company users');
                    }
                }
            }
        }
        $countries = CountryMaster::all();
        $privileges = Privilege::where('is_company_user',1)->get();
        // $companies = CompanyMaster::all();
        $companies = User::where('privileges',2)->get();
        return view('company_user_master.add', compact('countries','privileges','companies','state_allow','district_allow','taluka_allow'));
    }
    public function save(Request $request){
        if(!$this->getPermission('company_users','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'designation' => 'required',
            'country_id' => 'required|integer',
            'state_id' => 'required|integer',
            'district_id' => 'required|integer',
            'taluka_id' => 'required|integer',
            'village_id' => 'required|integer'
        ], [
            'mobile.required' => 'Mobile is required',
            'designation.required' => 'Designation is required',
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
            $user->company_id = Auth::user()->id;
            $management_level = 1;
            if($request->privileges==6)
                $management_level = 1;
            elseif($request->privileges==7)
                $management_level = 2;
            elseif($request->privileges==8)
                $management_level = 3;
            $user->management_level = $management_level;
            if(isset($request->mobile))
                $user->mobile = $request->mobile;
            $user->designation = $request->designation;
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
            $user->wallet_points = $wallet_points;
            
            $verified = 1;
            $user->verified = $verified;
            $user->added_by = Auth::user()->id;

            if($user->save()) {
               return redirect('/companyUserMaster')->with('success','Company User added successful');
            }
            else
               return redirect('/companyUserMaster')->with('error','Company User add failed');
        }
    }
    public function edit($id){
        if(!$this->getPermission('company_users','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $user = User::Where('id',$id)->first();
        if($user && (Auth::user()->privileges==1 || $user->added_by==Auth::user()->id)) {
            $countries = CountryMaster::all();
            $states = StateMaster::where('country_id',$user->country_id)->get();
            $districts = DistrictMaster::where('state_id',$user->state_id)->get();
            $talukas = TalukaMaster::where('district_id',$user->district_id)->get();
            $villages = VillageMaster::where('taluka_id',$user->taluka_id)->get();
            $privileges = Privilege::all();
            // $companies = CompanyMaster::all();
            $companies = User::where('privileges',2)->get();
            return view('company_user_master.edit',compact('user', 'countries', 'states', 'districts', 'talukas', 'villages', 'privileges', 'companies'));
        }
        return redirect('/companyUserMaster')->with('error','Company user not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('company_users','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'designation' => 'required',
            'country_id' => 'required|integer',
            'state_id' => 'required|integer',
            'district_id' => 'required|integer',
            'taluka_id' => 'required|integer',
            'village_id' => 'required|integer'
        ], [
            'mobile.required' => 'Mobile is required',
            'designation.required' => 'Designation is required',
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
            if(isset($request->mobile))
                $user->mobile = $request->mobile;
            $user->designation = $request->designation;
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
               return redirect('/companyUserMaster')->with('success','Company User updated successful');
            else
               return redirect('/companyUserMaster')->with('error','Company User update failed');
        }
        return redirect('/companyUserMaster')->with('error','Company user not found');
    }
    public function delete($id){
        if(!$this->getPermission('company_users','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if($id==1 || $id==2 || $id==3)
           return redirect('/companyUserMaster')->with('error','Can not delete this user');

        $user = User::Where('id',$id)->first();
        if($user) {
            if($user->delete())
               return redirect('/companyUserMaster')->with('success','Company User deleted successful');
            else
               return redirect('/companyUserMaster')->with('error','Company User delete failed');
        }
        return redirect('/companyUserMaster')->with('error','Company user not found');
    }
    public function companystate(){
        return view('company_user_master.companystate');
    }
    public function companydistrict(){
        return view('company_user_master.companydistrict');
    }
    public function companyUserArea($id){
        if(!$this->getPermission('company_users','is_read')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $user_id = $id;
        $user = User::Where('id',$id)->first();
        if($user && (Auth::user()->privileges==1 || $user->added_by==Auth::user()->id)) {
            if($user->management_level==1) {
                $companyUserAreaStates = CompanyUserArea::with('state')->where('management_level',$user->management_level)->where('user_id',$user_id)->get();
                return view('company_user_master.companystatelist',compact('companyUserAreaStates','user_id'));
            }
            else if($user->management_level==2) {
                $companyUserAreaDistricts = CompanyUserArea::with('state')->with('district')->where('management_level',$user->management_level)->where('user_id',$user_id)->get();
                return view('company_user_master.companydistrictlist',compact('companyUserAreaDistricts','user_id'));
            }
            else if($user->management_level==3) {
                $companyUserAreaTalukas = CompanyUserArea::with('state')->with('district')->where('management_level',$user->management_level)->where('user_id',$user_id)->get();
                return view('company_user_master.companytalukalist',compact('companyUserAreaTalukas','user_id'));
            }
        }
        return redirect('/companyUserMaster')->with('error','Company user not found');
    }
    public function companyUserAreaAdd($id){
        $user_id = $id;
        $user = User::Where('id',$id)->first();
        if($user && (Auth::user()->privileges==1 || $user->added_by==Auth::user()->id)) {
            if($user->management_level==1) {
                $countries = CountryMaster::all();
                return view('company_user_master.companystate',compact('countries','user_id'));
            }
            else if($user->management_level==2) {
                $states = StateMaster::get();
                return view('company_user_master.companydistrict',compact('states','user_id'));
            }
            else if($user->management_level==3) {
                $districts = DistrictMaster::get();
                return view('company_user_master.companytaluka',compact('districts','user_id'));
            }
        }
        return redirect('/companyUserMaster')->with('error','Company user not found');
    }
    public function getStateByCountryForCompany($id, $user_id){
        $user = User::Where('id',$user_id)->first();
        $states = StateMaster::Where('country_id',$id)->orderBy('name','asc')->get();
        $company_user_ids = User::Where('company_id',$user->company_id)->pluck('id');
        //$companyUserAreaStatesExclude = CompanyUserArea::where('management_level',$user->management_level)->where('user_id','<>',$user_id)->pluck('state_id');
        $companyUserAreaStatesExclude = CompanyUserArea::where('management_level',$user->management_level)->whereIn('user_id',$company_user_ids)->where('user_id','<>',$user_id)->where('company_id',$user->company_id)->pluck('state_id');
        $companyUserAreaStates = CompanyUserArea::where('management_level',$user->management_level)->where('user_id',$user_id)->pluck('state_id');
        $data = array();
        foreach ($states as $key => $value) {
            $state['id'] = $value->id;
            $state['name'] = $value->name;
            $state['disabled'] = in_array($value->id, $companyUserAreaStatesExclude->toArray())?true:false;
            $state['checked'] = in_array($value->id, $companyUserAreaStates->toArray())?true:false;
            array_push($data, $state);
        }
        return response()->json($data);
    }
    public function saveCompanyUserAreaStates(Request $request){
        $user = User::Where('id',$request->user_id)->first();
        $companyUserAreaStates = CompanyUserArea::where('management_level',$user->management_level)->where('user_id',$request->user_id)->pluck('state_id');
        $data = array();
        $keep_ids = array();
        $delete_ids = array();
        if(isset($request->states)) {
            foreach ($request->states as $key => $state) {
                if(!in_array($state, $companyUserAreaStates->toArray())) {
                    $companyUserArea['user_id'] = $request->user_id;
                    $companyUserArea['company_id'] = $user->company_id;
                    $companyUserArea['management_level'] = $user->management_level;
                    $companyUserArea['state_id'] = $state;
                    array_push($data, $companyUserArea);
                }
                else {
                    array_push($keep_ids, $state);
                }
            }
            $differenceArray = array_diff($companyUserAreaStates->toArray(), $keep_ids);
            CompanyUserArea::where('management_level',$user->management_level)->where('user_id',$request->user_id)->whereIn('district_id',$differenceArray)->delete();
        }
        else {
            CompanyUserArea::where('management_level',$user->management_level)->where('country_id',$request->country_id)->where('user_id',$request->user_id)->delete();
        }
        CompanyUserArea::insert($data);
        return redirect('/companyUserMaster')->with('success','Company User area updated successful');
    }
    public function getDistrictByStateForCompany($id, $user_id){
        $user = User::Where('id',$user_id)->first();
        $districts = DistrictMaster::Where('state_id',$id)->orderBy('name','asc')->get();
        $company_user_ids = User::Where('company_id',$user->company_id)->pluck('id');
        // $companyUserAreaDistrictsExclude = CompanyUserArea::where('management_level',$user->management_level)->where('state_id',$id)->where('user_id','<>',$user_id)->pluck('district_id');
        $companyUserAreaDistrictsExclude = CompanyUserArea::where('management_level',$user->management_level)->whereIn('user_id',$company_user_ids)->where('state_id',$id)->where('user_id','<>',$user_id)->pluck('district_id');
        $companyUserAreaDistricts = CompanyUserArea::where('management_level',$user->management_level)->where('state_id',$id)->where('user_id',$user_id)->pluck('district_id');
        $data = array();
        foreach ($districts as $key => $value) {
            $district['id'] = $value->id;
            $district['name'] = $value->name;
            $district['disabled'] = in_array($value->id, $companyUserAreaDistrictsExclude->toArray())?true:false;
            $district['checked'] = in_array($value->id, $companyUserAreaDistricts->toArray())?true:false;
            array_push($data, $district);
        }
        return response()->json($data);
    }
    public function saveCompanyUserAreaDistricts(Request $request){
        $user = User::Where('id',$request->user_id)->first();
        $companyUserAreaDistricts = CompanyUserArea::where('management_level',$user->management_level)->where('state_id',$request->state_id)->where('user_id',$request->user_id)->pluck('district_id');
        $data = array();
        $keep_ids = array();
        $delete_ids = array();
        if(isset($request->districts)) {
            foreach ($request->districts as $key => $district) {
                if(!in_array($district, $companyUserAreaDistricts->toArray())) {
                    $companyUserArea['user_id'] = $request->user_id;
                    $companyUserArea['company_id'] = $user->company_id;
                    $companyUserArea['management_level'] = $user->management_level;
                    $companyUserArea['state_id'] = $request->state_id;
                    $companyUserArea['district_id'] = $district;
                    array_push($data, $companyUserArea);
                }
                else {
                    array_push($keep_ids, $district);
                }
            }
            $differenceArray = array_diff($companyUserAreaDistricts->toArray(), $keep_ids);
            CompanyUserArea::where('management_level',$user->management_level)->where('state_id',$request->state_id)->where('user_id',$request->user_id)->whereIn('district_id',$differenceArray)->delete();
        }
        else {
            CompanyUserArea::where('management_level',$user->management_level)->where('state_id',$request->state_id)->where('user_id',$request->user_id)->delete();
        }
        CompanyUserArea::insert($data);
        return redirect('/companyUserMaster')->with('success','Company User area updated successful');
    }
    public function getTalukaByDistrictForCompany($id, $user_id){
        $user = User::Where('id',$user_id)->first();
        $talukas = TalukaMaster::Where('district_id',$id)->orderBy('name','asc')->get();
        $company_user_ids = User::Where('company_id',$user->company_id)->pluck('id');
        // $companyUserAreaTalukasExclude = CompanyUserArea::where('management_level',$user->management_level)->where('district_id',$id)->where('user_id','<>',$user_id)->pluck('taluka_id');
        $companyUserAreaTalukasExclude = CompanyUserArea::where('management_level',$user->management_level)->whereIn('user_id',$company_user_ids)->where('district_id',$id)->where('user_id','<>',$user_id)->pluck('taluka_id');
        $companyUserAreaTalukas = CompanyUserArea::where('management_level',$user->management_level)->where('district_id',$id)->where('user_id',$user_id)->pluck('taluka_id');
        $data = array();
        foreach ($talukas as $key => $value) {
            $taluka['id'] = $value->id;
            $taluka['name'] = $value->name;
            $taluka['disabled'] = in_array($value->id, $companyUserAreaTalukasExclude->toArray())?true:false;
            $taluka['checked'] = in_array($value->id, $companyUserAreaTalukas->toArray())?true:false;
            array_push($data, $taluka);
        }
        return response()->json($data);
    }
    public function saveCompanyUserAreaTalukas(Request $request){
        $user = User::Where('id',$request->user_id)->first();
        $companyUserAreaTalukas = CompanyUserArea::where('management_level',$user->management_level)->where('district_id',$request->district_id)->where('user_id',$request->user_id)->pluck('taluka_id');
        $data = array();
        $keep_ids = array();
        $delete_ids = array();
        if(isset($request->talukas)) {
            foreach ($request->talukas as $key => $taluka) {
                if(!in_array($taluka, $companyUserAreaTalukas->toArray())) {
                    $companyUserArea['user_id'] = $request->user_id;
                    $companyUserArea['company_id'] = $user->company_id;
                    $companyUserArea['management_level'] = $user->management_level;
                    $companyUserArea['district_id'] = $request->district_id;
                    $companyUserArea['taluka_id'] = $taluka;
                    array_push($data, $companyUserArea);
                }
                else {
                    array_push($keep_ids, $taluka);
                }
            }
            $differenceArray = array_diff($companyUserAreaTalukas->toArray(), $keep_ids);
            CompanyUserArea::where('management_level',$user->management_level)->where('district_id',$request->district_id)->where('user_id',$request->user_id)->whereIn('taluka_id',$differenceArray)->delete();
        }
        else {
            CompanyUserArea::where('management_level',$user->management_level)->where('district_id',$request->district_id)->where('user_id',$request->user_id)->delete();
        }
        CompanyUserArea::insert($data);
        return redirect('/companyUserMaster')->with('success','Company User area updated successful');
    }
}
