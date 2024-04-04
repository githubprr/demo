<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ForumQuestion;
use App\ForumAnswer;
use App\CompanyUserArea;
use App\User;
use Auth;
use File;
use Validator;

class ForumController extends Controller
{
    // public function index(){
    //     if(Auth::user()->privileges==1)
    //         $forums = ForumQuestion::with('company')->with('item')->withCount('forum_answers')->with('user')->orderBy('id','desc')->get();
    //     else {
    //         $forums = ForumQuestion::with('company')
    //             ->with('item')
    //             ->withCount('forum_answers')
    //             ->with('user')
    //             ->join('users', 'users.id', 'forum_questions.user_id')
    //             //->where('users.district_id', Auth::user()->district_id)
    //             ->where('forum_questions.company_id', Auth::user()->id)
    //             ->orderBy('id','desc')
    //             ->get();
    //     }
    // 	return view('forum.list', compact('forums'));
    // }
    public function indexold(){
        if(!$this->getPermission('forum','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if(Auth::user()->privileges==1)
            $forums = ForumQuestion::with('company')->with('item')->withCount('forum_answers')->with('user')->orderBy('id','desc')->get();
        else if(Auth::user()->privileges==2) {
            $forums = ForumQuestion::with('company')
                ->with('item')
                ->withCount('forum_answers')
                ->with('user')
                ->join('users', 'users.id', 'forum_questions.user_id')
                //->where('users.district_id', Auth::user()->district_id)
                ->where('forum_questions.company_id', Auth::user()->id)
                ->orWhere('forum_questions.all_company', 1)
                ->orderBy('id','desc')
                ->get();
        }
        else {
            $management_level = Auth::user()->management_level;
            $company = User::where('id',Auth::user()->added_by)->first();
            $forums = ForumQuestion::with('company')
                ->with('item')
                ->withCount('forum_answers')
                ->with('user')
                ->join('users', 'users.id', 'forum_questions.user_id')
                ->where('forum_questions.company_id', Auth::user()->added_by);
            if($management_level==1) {
                $companyUserAreaStates = CompanyUserArea::where('management_level',$management_level)->where('user_id',Auth::user()->id)->pluck('state_id');
                $forums = $forums->whereIn('users.state_id', $companyUserAreaStates);
            }
            else if($management_level==2) {
                $companyUserAreaDistricts = CompanyUserArea::where('management_level',$management_level)->where('user_id',Auth::user()->id)->pluck('district_id');
                $forums = $forums->whereIn('users.district_id', $companyUserAreaDistricts);
            }
            else if($management_level==3) {
                $companyUserAreaTalukas = CompanyUserArea::where('management_level',$management_level)->where('user_id',Auth::user()->id)->pluck('taluka_id');
                $forums = $forums->whereIn('users.taluka_id', $companyUserAreaTalukas);
            }
            $forums = $forums->orWhere('forum_questions.all_company', 1);
            $forums = $forums->orderBy('id','desc')->get();
        }
        return view('forum.list', compact('forums'));
    }
    public function index(){
        if(!$this->getPermission('forum','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if(Auth::user()->privileges==1)
            $forums = ForumQuestion::with('company')->with('item')->withCount('forum_answers')->with('user')->orderBy('id','desc')->get();
        else if(Auth::user()->privileges==2) {
            $forums = ForumQuestion::with('company')
                ->with('item')
                ->withCount('forum_answers')
                ->with('user')
                ->join('users', 'users.id', 'forum_questions.user_id')
                //->where('users.district_id', Auth::user()->district_id)
                ->where('forum_questions.company_id', Auth::user()->id)
                ->orWhere('forum_questions.all_company', 1)
                ->orderBy('id','desc')
                ->get();
        }
        else {
            $management_level = Auth::user()->management_level;
            $company = User::where('id',Auth::user()->added_by)->first();
            $forums = ForumQuestion::
                with('item')
                ->withCount('forum_answers')
                ->with('user')
                ->join('users', 'users.id', 'forum_questions.user_id')
                // ->join('users AS company_users', 'company_users.id', 'forum_questions.company_id')
                ->where('forum_questions.company_id', Auth::user()->added_by);
            if($management_level==1) {
                $companyUserAreaStates = CompanyUserArea::where('management_level',$management_level)->where('user_id',Auth::user()->id)->pluck('state_id');
                $forums = $forums->whereIn('users.state_id', $companyUserAreaStates)
                            ->orWhere(function($query) use ($companyUserAreaStates){
                                $query->where('forum_questions.all_company', 1)
                                    ->whereIn('users.state_id', $companyUserAreaStates);
                            });
            }
            else if($management_level==2) {
                $companyUserAreaDistricts = CompanyUserArea::where('management_level',$management_level)->where('user_id',Auth::user()->id)->pluck('district_id');
                $forums = $forums->whereIn('users.district_id', $companyUserAreaDistricts)
                            ->orWhere(function($query) use ($companyUserAreaDistricts){
                                $query->where('forum_questions.all_company', 1)
                                    ->whereIn('users.district_id', $companyUserAreaDistricts);
                            });
            }
            else if($management_level==3) {
                $companyUserAreaTalukas = CompanyUserArea::where('management_level',$management_level)->where('user_id',Auth::user()->id)->pluck('taluka_id');
                $forums = $forums->whereIn('users.taluka_id', $companyUserAreaTalukas)
                            ->orWhere(function($query) use ($companyUserAreaTalukas){
                                $query->where('forum_questions.all_company', 1)
                                    ->whereIn('users.taluka_id', $companyUserAreaTalukas);
                            });
            }
            $forums = $forums->orderBy('id','desc')->get();
        }
        return view('forum.list', compact('forums'));
    }
    public function addForumAnswer($id){
        if(!$this->getPermission('forum','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$forum = ForumQuestion::where('id',$id)->first();
        return view('forum.add_answer', compact('forum'));
    }
    // public function save(Request $request){
    //     $country = new CountryMaster;
    //     $country->name = $request->name;
    //     if($country->save())
    //        return redirect('/countryMaster')->with('success','Country added successful');
    // 	else
    //        return redirect('/countryMaster')->with('error','Country add failed');
    // }
    public function saveForumAnswer(Request $request){
        if(!$this->getPermission('forum','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        // $input = $request->all();
        // $request->validate([
        //     'answer' => 'required|alpha'
        // ]);
        // $validator = Validator::make($input, [
        //     'name' => 'required|alpha'
        // ]);
        // if($validator->fails()){
        //     // dd($validator->errors());
        //     return back()->with('error',$validator->errors());       
        // }
        $forumAnswer = new ForumAnswer;
        $forumAnswer->forum_question_id = $request->forum_question_id;
        $forumAnswer->answer = $request->answer;
        $forumAnswer->user_id = Auth::user()->id;
        if($request->hasFile('image')){
            $file= $_FILES['image']['name'];
            $var=explode(".",$file);
            $ext='.'.end($var);
            $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
            $filename =  $value.$ext;
            $filepath = public_path('uploads/forum/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);

            $forumAnswer->image = $filename;
        }
        if(Auth::user()->privileges==2)
            $forumAnswer->company_id = Auth::user()->id;
        else if(Auth::user()->privileges==6 || Auth::user()->privileges==7 || Auth::user()->privileges==8)
            $forumAnswer->company_id = Auth::user()->added_by;
        if($forumAnswer->save())
           return back()->with('success','Forum Answer added successful');
        else
           return back()->with('failed','Forum Answer add failed');
    }
    public function editForumAnswer($id){
        if(!$this->getPermission('forum','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$forumAnswer = ForumAnswer::with('forum_question')->Where('id',$id)->first();
        if($forumAnswer) {
            return view('forum.edit_answer',compact('forumAnswer'));
        }
        return redirect('/forums')->with('error','Forum answer not found');
    }
    public function updateForumAnswer(Request $request){
        if(!$this->getPermission('forum','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$forumAnswer = ForumAnswer::Where('id',$request->id)->first();
        if($forumAnswer) {
        	$forumAnswer->answer = $request->answer;
            if($request->hasFile('image')){
                $image_path = public_path() . '/uploads/forum/'.$forumAnswer->image;
                if(File::exists($image_path)) {
                    File::delete($image_path);
                }
                $file= $_FILES['image']['name'];
                $var=explode(".",$file);
                $ext='.'.end($var);
                $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename =  $value.$ext;
                $filepath = public_path('uploads/forum/');

                if(!File::isDirectory($filepath))
                    File::makeDirectory($filepath, 0777, true, true);

                move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);

                $forumAnswer->image = $filename;
            }
        	if($forumAnswer->save())
               return redirect('/detailsForum/'.$request->forum_question_id)->with('success','Forum Answer updated successful');
        	else
               return redirect('/detailsForum/'.$request->forum_question_id)->with('error','Forum Answer update failed');
        }
        return redirect('/forums')->with('error','Forum answer not found');
    }
    public function deleteForumAnswer($id){
        if(!$this->getPermission('forum','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$forumAnswer = ForumAnswer::Where('id',$id)->first();
        if($forumAnswer) {
        	if($forumAnswer->delete())
               return back()->with('success','Forum Answer deleted successful');
        	else
               return back()->with('error','Forum Answer delete failed');
        }
        return redirect('/forums')->with('error','Forum answer not found');
    }
    public function details($id){
        if(!$this->getPermission('forum','is_read')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $can_reply = false;
        $allowed_answers_count_error = false;
        $user = User::where('id',Auth::user()->id)->first();
        if(Auth::user()->privileges==2) {
            $answers = ForumAnswer::where('company_id',Auth::user()->id)->count();
            if($answers==0 || $answers>0) {
                $allowed_answers_count = 0;
                $subscription_value = $this->getSubscriptionDetails('question_answer_nos');
                if($subscription_value!="-1") {
                    $allowed_answers_count = $subscription_value;
                    if($subscription_value==null)
                        $allowed_answers_count_error = true;
                        // return redirect('/forums')->with('error','You/your company dont have any subscription to add question answer');
                    else if($answers>=$allowed_answers_count) {
                        $allowed_answers_count_error = true;
                        // return redirect('/forums')->with('error','Your/your company subscription allowed you to add only '.$allowed_answers_count.' question answer');
                    }
                }
            }
        }
        if(Auth::user()->privileges==1)
            $forums = ForumQuestion::with('company')->with('item')->withCount('forum_answers')->with('user')->Where('id',$id)->pluck('forum_questions.id')->toArray();
        else if(Auth::user()->privileges==2) {
            $forums = ForumQuestion::with('company')
                ->with('item')
                ->withCount('forum_answers')
                ->with('user')
                ->join('users', 'users.id', 'forum_questions.user_id')
                //->where('users.district_id', Auth::user()->district_id)
                ->where('forum_questions.company_id', Auth::user()->id)
                ->orWhere('forum_questions.all_company', 1)
                ->orderBy('id','desc')
                ->pluck('forum_questions.id')->toArray();
                // ->get();
        }
        else {
            $management_level = Auth::user()->management_level;
            $company = User::where('id',Auth::user()->added_by)->first();
            $forums = ForumQuestion::
                with('item')
                ->withCount('forum_answers')
                ->with('user')
                ->join('users', 'users.id', 'forum_questions.user_id')
                // ->join('users AS company_users', 'company_users.id', 'forum_questions.company_id')
                ->where('forum_questions.company_id', Auth::user()->added_by);
            if($management_level==1) {
                $companyUserAreaStates = CompanyUserArea::where('management_level',$management_level)->where('user_id',Auth::user()->id)->pluck('state_id');
                $forums = $forums->whereIn('users.state_id', $companyUserAreaStates)
                            ->orWhere(function($query) use ($companyUserAreaStates){
                                $query->where('forum_questions.all_company', 1)
                                    ->whereIn('users.state_id', $companyUserAreaStates);
                            });
            }
            else if($management_level==2) {
                $companyUserAreaDistricts = CompanyUserArea::where('management_level',$management_level)->where('user_id',Auth::user()->id)->pluck('district_id');
                $forums = $forums->whereIn('users.district_id', $companyUserAreaDistricts)
                            ->orWhere(function($query) use ($companyUserAreaDistricts){
                                $query->where('forum_questions.all_company', 1)
                                    ->whereIn('users.district_id', $companyUserAreaDistricts);
                            });
            }
            else if($management_level==3) {
                $companyUserAreaTalukas = CompanyUserArea::where('management_level',$management_level)->where('user_id',Auth::user()->id)->pluck('taluka_id');
                $forums = $forums->whereIn('users.taluka_id', $companyUserAreaTalukas)
                            ->orWhere(function($query) use ($companyUserAreaTalukas){
                                $query->where('forum_questions.all_company', 1)
                                    ->whereIn('users.taluka_id', $companyUserAreaTalukas);
                            });
            }
            // $forums = $forums->orderBy('id','desc')->get();
            $forums = $forums->pluck('forum_questions.id')->toArray();
        }
        if(count($forums) && in_array($id, $forums)) {
            $forum = ForumQuestion::with('company')->with('item')->with('forum_answers')->Where('id',$id)->first();
            if(!$allowed_answers_count_error) {
                if($user->privileges==1 || ($user->privileges==2 && $forum->company_id==$forum->company->id) || $forum->all_company==1)
                    $can_reply = true;
                if($user->privileges==1 || (($user->privileges==6 || $user->privileges==7 || $user->privileges==8) && $forum->company_id==$user->added_by) || $forum->all_company==1)
                    $can_reply = true;
            }
            if($forum && count($forum->forum_answers)) {
                $already_answer = $forum->forum_answers->where('user_id',Auth::user()->id)->first();
                if($already_answer)
                    $can_reply = false;
            }
            if($allowed_answers_count_error!=null)
                return view('forum.details',compact('forum','can_reply'))->with('allowed_answers_count_error','Your/your company subscription allowed you to add only '.$allowed_answers_count.' forum answer');
            else
                return view('forum.details',compact('forum','can_reply'));
        }
        return redirect('/forums')->with('error','Forum not found');
    }
    public function stats(){
        if(!$this->getPermission('forum','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $forum_count = ForumQuestion::count();
        $forum_private_count = ForumQuestion::where('visibility',1)->count();
        $forum_public_count = ForumQuestion::where('visibility',0)->count();
        // $forum_answered_count = 0;
        // $forum_answered = ForumQuestion::withCount('forum_answers')->get();
        // foreach ($forum_answered as $key => $value) {
        //     if($value->forum_answers_count>0)
        //         $forum_answered_count++;
        // }
        $forum_answered_count = ForumAnswer::distinct('forum_question_id')->count('forum_question_id');
        $forum_not_answered_count = ForumQuestion::
            leftJoin('forum_answers','forum_questions.id','forum_answers.forum_question_id')
            ->where('forum_answers.id',null)
            ->count();
        return view('forum.stats',compact('forum_count','forum_private_count','forum_public_count','forum_answered_count','forum_not_answered_count'));
    }
}
