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
use App\CompanyReview;
use App\ForumQuestion;
use App\ForumAnswer;
use App\Subscription;
use App\SpecialAddon;
use App\CompanyUserArea;
use Auth;
use File;
use Hash;
use Validator;
use DB;

class CompanyMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('companies','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $companies = User::withCount('items')->where('privileges',2)->where('id','<>',2)->where('id','<>',3)->orderBy('id','desc')->get();
    	return view('company_master.list', compact('companies'));
    }
    public function add(){
        if(!$this->getPermission('companies','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $countries = CountryMaster::all();
        $privileges = Privilege::all();
        return view('company_master.add', compact('countries','privileges'));
    }
    public function save(Request $request){
        if(!$this->getPermission('companies','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'designation' => 'required',
            'country_id' => 'required|integer',
            'state_id' => 'required|integer'
            // 'district_id' => 'required|integer',
            // 'taluka_id' => 'required|integer',
            // 'village_id' => 'required|integer'
        ], [
            'mobile.required' => 'Mobile is required',
            'designation.required' => 'Designation is required',
            'country_id.required' => 'Country is required',
            'state_id.required' => 'State is required'
            // 'district_id.required' => 'District is required',
            // 'taluka_id.required' => 'Taluka is required',
            // 'village_id.required' => 'Village is required'
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

            $company = new User;
            $company->name = $request->name;
            $company->email = $request->email;
            $company->password = Hash::make($request->password);
            $company->privileges = 2;
            $company->photo = $filename;
            if(isset($request->mobile))
                $company->mobile = $request->mobile;
            $company->designation = $request->designation;
            if(isset($request->country_id))
                $company->country_id = $request->country_id;
            if(isset($request->state_id))
                $company->state_id = $request->state_id;
            if(isset($request->district_id))
                $company->district_id = $request->district_id;
            if(isset($request->taluka_id))
                $company->taluka_id = $request->taluka_id;
            if(isset($request->village_id))
                $company->village_id = $request->village_id;

            $wallet_points = 0;
            $company->wallet_points = $wallet_points;
            
            $verified = 1;
            $company->verified = $verified;

            if($company->save()) {
                return redirect('/companyMaster')->with('success','Company added successful');
            }
            else
               return redirect('/companyMaster')->with('error','Company add failed');
        }
    }
    public function edit($id){
        if(!$this->getPermission('companies','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $company = User::Where('id',$id)->first();
        if($company) {
            $countries = CountryMaster::all();
            $states = StateMaster::where('country_id',$company->country_id)->get();
            $districts = DistrictMaster::where('state_id',$company->state_id)->get();
            $talukas = TalukaMaster::where('district_id',$company->district_id)->get();
            $villages = VillageMaster::where('taluka_id',$company->taluka_id)->get();
            $privileges = Privilege::all();
            // $companies = CompanyMaster::all();
            $companies = User::where('privileges',2)->get();
            return view('company_master.edit',compact('company', 'countries', 'states', 'districts', 'talukas', 'villages', 'privileges', 'companies'));
        }
        return redirect('/companyMaster')->with('error','Company not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('companies','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'designation' => 'required',
            'country_id' => 'required|integer',
            'state_id' => 'required|integer'
            // 'district_id' => 'required|integer',
            // 'taluka_id' => 'required|integer',
            // 'village_id' => 'required|integer'
        ], [
            'mobile.required' => 'Mobile is required',
            'designation.required' => 'Designation is required',
            'country_id.required' => 'Country is required',
            'state_id.required' => 'State is required'
            // 'district_id.required' => 'District is required',
            // 'taluka_id.required' => 'Taluka is required',
            // 'village_id.required' => 'Village is required'
        ]);
        if($validator->fails())
            return back()->with('validations',$validator->errors());

        $company = User::Where('id',$request->id)->first();
        if($company) {
            if($request->hasFile('photo')){
                $image_path = public_path() . '/uploads/user/'.$company->photo;
                if(File::exists($image_path)) {
                    File::delete($image_path);
                }
                $file= $_FILES['photo']['name'];
                $var=explode(".",$file);
                $ext='.'.end($var);
                $company_id = Auth::user()->id;
                $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename =  $value.'-'.$company_id .$ext;
                $filepath = public_path('uploads/user/');

                if(!File::isDirectory($filepath))
                    File::makeDirectory($filepath, 0777, true, true);

                move_uploaded_file($_FILES['photo']['tmp_name'], $filepath.$filename);
                $company->photo = $filename;
            }
            $company->name = $request->name;
            $company->district_id = null;
            $company->taluka_id = null;
            $company->village_id = null;
            if(isset($request->company_name))
                $company->company_name = $request->company_name;
            if(isset($request->designation))
                $company->designation = $request->designation;
            if(isset($request->mobile))
                $company->mobile = $request->mobile;
            if(isset($request->total_experience))
                $company->total_experience = $request->total_experience;
            if(isset($request->experience_in))
                $company->experience_in = $request->experience_in;
            if(isset($request->address))
                $company->address = $request->address;
            if(isset($request->country_id))
                $company->country_id = $request->country_id;
            if(isset($request->state_id))
                $company->state_id = $request->state_id;
            if(isset($request->district_id))
                $company->district_id = $request->district_id;
            if(isset($request->taluka_id))
                $company->taluka_id = $request->taluka_id;
            if(isset($request->village_id))
                $company->village_id = $request->village_id;
            if($company->save())
               return redirect('/companyMaster')->with('success','User updated successful');
            else
               return redirect('/companyMaster')->with('error','User update failed');
        }
        return redirect('/companyMaster')->with('error','Company not found');
    }
    public function delete($id){
    	if(!$this->getPermission('companies','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$company = User::Where('id',$id)->first();
        if($company) {
            if($company->delete())
               return redirect('/companyMaster')->with('success','Company deleted successful');
            else
               return redirect('/companyMaster')->with('error','Company delete failed');
        }
        return redirect('/companyMaster')->with('error','Company not found');
    }
    public function tree($id){
        $companies = User::with('privilege')
            ->with('child_company.privilege')
            ->where('privileges',2)
            ->where('id',$id)
            ->first();

        $lang = 'name';
        $header = request()->has('lang');
        if($header=='hn') {
            $lang = 'name_hn as name';
        }
        else if($header=='mr') {
            $lang = 'name_mr as name';
        }
        foreach ($companies->child_company as $key => $user) {
            if($user->management_level==1 || $user->management_level==2 || $user->management_level==3) {
                $company_area = CompanyUserArea::select('company_user_area.id',$lang);
                if($user->management_level==1) {
                        $company_area = $company_area->join('states','company_user_area.state_id','states.id');
                }
                else if($user->management_level==2) {
                        $company_area = $company_area->join('districts','company_user_area.district_id','districts.id');
                }
                else if($user->management_level==3) {
                        $company_area = $company_area->join('talukas','company_user_area.taluka_id','talukas.id');
                }
                $company_area = $company_area->where('company_user_area.user_id',$user->id)
                    ->where('company_user_area.management_level',$user->management_level)
                    ->pluck($lang)
                    ->implode(', ');
                
                $user->company_area = null;
                if($company_area) {
                    $user->company_area = $company_area;
                }
            }
        }
        
        return view('company_master.tree', compact('companies'));
    }
    public function apiSaveReview(Request $request){
        $user = auth()->guard('api')->user();
        $company_review_exitst = CompanyReview::where('company_id',$request->company_id)->where('user_id',$user->id)->exists();
        if(!$company_review_exitst) {
            $company_review = new CompanyReview;
            $company_review->company_id = $request->company_id;
            $company_review->user_id = $user->id;
            $company_review->rating = $request->rating;
            if(isset($request->review))
                $company_review->review = urldecode($request->review);
            $company_review->save();
            if($company_review) {
                return response()->json([
                    'errorCode' => 0,
                    'message' => 'Company review added successful'
                ]);
            }

            return response()->json([
                'errorCode' => 1,
                'message' => 'Company review add failed'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'You have already given company review'
        ]);
    }
    public function apiGetCompanyDetails($id){
        $user = auth()->guard('api')->user();
        $company_id = $id;
        if($user->privileges==6 || $user->privileges==7 || $user->privileges==8)
            $company_id = $user->company_id;

        $company = User::
            select('id','name','email','mobile','photo',DB::raw('(SELECT avg(rating) FROM company_reviews WHERE company_reviews.company_id=users.id) AS avg_rating'))
            ->withCount('child_company')
            ->where('id',$company_id)
            ->first();
        if($company) {
            if($company->avg_rating==null)
                $company->avg_rating = 0;
            else
                $company->avg_rating = round($company->avg_rating,2);

            $rating_array = array();
            for ($i=0; $i < 5; $i++) { 
                $new_rating_group['rating'] = $i+1;
                $new_rating_group['total'] = 0;
                array_push($rating_array, $new_rating_group);
            }
            $rating_group = CompanyReview::where('company_id',$id)->select('rating', DB::raw('count(rating) as total'))->groupBy('rating')->orderBy('rating')->get();
            if(count($rating_group)) {
                foreach ($rating_group as $key => $value) {
                    $rating_array[$value->rating-1]['total'] = $value->total;
                }
            }
            $company->rating_group = $rating_array;
            
            $company->question_asked_count = 0;
            $company->question_answered_count = 0;
            $company->question_pending_count = 0;
            $company_ids = ForumQuestion::where('company_id',$id)->pluck('id');
            if($company_ids) {
                $companies = ForumAnswer::whereIn('forum_question_id',$company_ids)
                    ->select('forum_question_id',DB::raw('COUNT(forum_answers.id) as tt'))
                    ->where('company_id',$user->id)
                    ->groupBy('forum_question_id')
                    ->get();

                if($companies) {
                    $company->question_asked_count = count($company_ids);
                    $company->question_answered_count = count($companies);
                    $company->question_pending_count = count($company_ids)-count($companies);
                }
            }

            // $subscription_array = array();            
            $subscription_employee_nos = 0;
            // array_push($subscription_array, $subscription_item1);
            $subscription_question_answer_nos = 0;
            // array_push($subscription_array, $subscription_item2);
            $used_employee_nos = 0;
            // array_push($subscription_array, $used_item1);
            $used_question_answer_nos = 0;
            // array_push($subscription_array, $used_item2);
            
            $subscription = Subscription::where('user_id',$id)->first();
            if($subscription) {
                // $subscription_array[0]['subscription_employee_nos'] = (int)$subscription->employee_nos;
                // $subscription_array[1]['subscription_question_answer_nos'] = (int)$subscription->question_answer_nos;
                $subscription_employee_nos = (int)$subscription->employee_nos;
                $subscription_question_answer_nos = (int)$subscription->question_answer_nos;
            }
            // $subscription_array[2]['used_employee_nos'] = User::where('added_by',$id)->count('id');
            // $subscription_array[3]['used_question_answer_nos'] = ForumAnswer::where('company_id',$id)->groupBy('forum_question_id')->count('id');
            $used_employee_nos = User::where('added_by',$id)->count('id');
            $used_question_answer_nos = ForumAnswer::where('company_id',$id)->groupBy('forum_question_id')->count('id');
            
            // $company->subscription_group = $subscription_array;
            $company->subscription_employee_nos = $subscription_employee_nos;
            $company->subscription_question_answer_nos = $subscription_question_answer_nos;
            $company->used_employee_nos = $used_employee_nos;
            $company->used_question_answer_nos = $used_question_answer_nos;
            
            $company->my_rating = CompanyReview::where('company_id',$id)->where('user_id',$user->id)->select('rating','review')->first();
            return response()->json([
                'errorCode' => 0,
                'data' => $company,
                'message' => 'Get company details successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get company details failed'
        ]);
    }
}