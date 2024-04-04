<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\ForumQuestion;
use App\ForumAnswer;
use App\PointMaster;
use App\PointHistory;
use App\User;
use App\CompanyUserArea;
use Auth;
use File;

class ForumController extends Controller
{
    public function questionAdd(Request $request)
    {
        $user = auth()->guard('api')->user();
        $valid = validator($request->only('company_id','item_id','question','image','visibility'), [
            'question' => 'required',
            'visibility' => 'required',
            'company_id' => 'required',
			'item_id' => 'required'
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $isWalletPoint = $this->canDoActivity('ask_question');
        if(!$isWalletPoint) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'You dont have enough points in your wallet'
            ]);
        }

        $data = request()->only('company_id','item_id','question','image','visibility');

        $finalFileName = null;

        if($request->hasFile('image')){
            $file= $_FILES['image']['name'];
            $var=explode(".",$file);
            $ext='.'.end($var);
            $user_id = $user->id;
            $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
            $filename =  $value.'-'.$user_id .$ext;
            $filepath = public_path('uploads/forum/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);
            $finalFileName = 'uploads/forum/'.$filename;
        }
		
        $forumQuestion = ForumQuestion::create([
            'company_id' => $data['company_id'],
            'item_id' => $data['item_id'],
            'question' => urldecode($data['question']),
			'image' => $finalFileName,
            'visibility' => $data['visibility'],
            'user_id' => Auth::user()->id,
            'status' => 0,
			'all_company' => ($data['company_id']==2 || $data['company_id']=='2')?1:0
        ]);

        $points_master = PointMaster::where('slug','ask_question')->first();
        if($points_master) {
            $wallet_points = $points_master->point;
            $user->wallet_points -= $wallet_points;
            $user->save();

            $message = "Substracted ".$wallet_points." points for ask question";
            $pointHistory = new PointHistory;
            $pointHistory->message = $message;
            $pointHistory->points = $wallet_points;
            $pointHistory->user_id = $user->id;
            $pointHistory->save();
        }
		
		return response()->json([
            'errorCode' => 0,
            'data' => $forumQuestion,
            'message' => 'Forum question added successful'
        ]);
    }
	
	public function forumMyQuestions()
    {
		$forumQuestions = ForumQuestion::with('company')->with('item')->where('user_id',Auth::user()->id)->orderBy('id','desc')->get();
		if(sizeof($forumQuestions)>0) {
            return response()->json([
                'errorCode' => 0,
                'data' => $forumQuestions,
                'message' => 'Get forum my questions successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get forum my questions failed'
        ]);
    }

    public function questionAnswers($id)
    {
		$forum = ForumQuestion::with('company')->with('forum_answers.user')->find($id);
		if($forum) {
            return response()->json([
                'errorCode' => 0,
                'data' => $forum,
                'message' => 'Get forum question answers successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get forum question answers failed'
        ]);
    }

    public function acceptAnswer($id)
    {
        $forum = ForumAnswer::where('id',$id)->first();
        if(isset($forum)) {
            ForumAnswer::where('forum_question_id',$forum->forum_question_id)->update(['accepted'=>null]);
            $forum->accepted = 1;
            $forum->save();

            ForumQuestion::where('id',$forum->forum_question_id)->update(['status'=>1]);
            return response()->json([
                'errorCode' => 0,
                'message' => 'Forum question answer accepted successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Forum question answer accept failed'
        ]);
    }

    public function getAllForums()
    {
        $forumQuestions = ForumQuestion::with('user')->with('company')->with('item')->where('visibility',0)->orderBy('id', 'desc')->get();
        if(count($forumQuestions)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $forumQuestions,
                'message' => 'Get forum questions successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get forum questions failed'
        ]);
    }

    public function forumCompanyQuestionsold()
    {
        $user = auth()->guard('api')->user();
        if($user->privileges==6 || $user->privileges==7 || $user->privileges==8)
            $forumQuestions = ForumQuestion::with('user')->with('company')->with('item')->where('company_id',$user->company_id)->orWhere('all_company',1)->orderBy('id','desc')->get();
        else
            $forumQuestions = ForumQuestion::with('user')->with('company')->with('item')->where('company_id',$user->id)->orWhere('all_company',1)->orderBy('id','desc')->get();
        if(count($forumQuestions)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $forumQuestions,
                'message' => 'Get forum questions successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get forum questions failed'
        ]);
    }

    public function forumCompanyQuestions()
    {
        $user = auth()->guard('api')->user();
        if($user->privileges==6 || $user->privileges==7 || $user->privileges==8) {
            // $forumQuestions = ForumQuestion::with('user')->with('company')->with('item')->where('company_id',$user->company_id)->orWhere('all_company',1)->orderBy('id','desc')->get();
            $management_level = $user->management_level;
            $company = User::where('id',$user->added_by)->first();
            $forums = ForumQuestion::with('company')
                ->with('item')
                ->withCount('forum_answers')
                ->with('user')
                ->join('users', 'users.id', 'forum_questions.user_id')
                ->where('forum_questions.company_id', $user->added_by);
            if($management_level==1) {
                $companyUserAreaStates = CompanyUserArea::where('management_level',$management_level)->where('user_id',$user->id)->pluck('state_id');
                $forums = $forums->whereIn('users.state_id', $companyUserAreaStates)
                            ->orWhere(function($query) use ($companyUserAreaStates){
                                $query->where('forum_questions.all_company', 1)
                                    ->whereIn('users.state_id', $companyUserAreaStates);
                            });
            }
            else if($management_level==2) {
                $companyUserAreaDistricts = CompanyUserArea::where('management_level',$management_level)->where('user_id',$user->id)->pluck('district_id');
                $forums = $forums->whereIn('users.district_id', $companyUserAreaDistricts)
                            ->orWhere(function($query) use ($companyUserAreaDistricts){
                                $query->where('forum_questions.all_company', 1)
                                    ->whereIn('users.district_id', $companyUserAreaDistricts);
                            });
            }
            else if($management_level==3) {
                $companyUserAreaTalukas = CompanyUserArea::where('management_level',$management_level)->where('user_id',$user->id)->pluck('taluka_id');
                $forums = $forums->whereIn('users.taluka_id', $companyUserAreaTalukas)
                            ->orWhere(function($query) use ($companyUserAreaTalukas){
                                $query->where('forum_questions.all_company', 1)
                                    ->whereIn('users.taluka_id', $companyUserAreaTalukas);
                            });
            }
            // $forums = $forums->orWhere('forum_questions.all_company', 1);
            $forumQuestions = $forums->orderBy('id','desc')->get();
        }
        else {
            $forumQuestions = ForumQuestion::with('user')
                ->with('company')
                ->with('item')
                ->where('company_id',$user->id)
                ->orWhere('all_company',1)
                ->orderBy('id','desc')
                ->get();
        }
        if(count($forumQuestions)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $forumQuestions,
                'message' => 'Get forum questions successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get forum questions failed'
        ]);
    }

    public function forumReply(Request $request)
    {
        $user = auth()->guard('api')->user();
        $data = request()->only('forum_question_id','answer');

        $forumAnswer = new ForumAnswer;
        $forumAnswer->forum_question_id = $request->forum_question_id;
        $forumAnswer->answer = urldecode($request->answer);
        $forumAnswer->user_id = $user->id;

        $finalFileName = null;

        if($request->hasFile('image')){
            $file= $_FILES['image']['name'];
            $var=explode(".",$file);
            $ext='.'.end($var);
            $user_id = $user->id;
            $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
            $filename =  $value.'-'.$user_id .$ext;
            $filepath = public_path('uploads/forum/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);
            $finalFileName = 'uploads/forum/'.$filename;
            $forumAnswer->image = $finalFileName;
        }
        if($forumAnswer->save()) {
            return response()->json([
                'errorCode' => 0,
                'message' => 'Forum question answered successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Forum question answer failed'
        ]);
    }
    public function questionUpdate(Request $request)
    {
        $user = auth()->guard('api')->user();
        $valid = validator($request->only('id','question'), [
            'id' => 'required',
            'question' => 'required'
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $data = request()->only('id','question');

        $forumQuestion = ForumQuestion::withCount('forum_answers')->where('id',$data['id'])->first();
        if($forumQuestion) {
            if(isset($forumQuestion->forum_answers_count) && $forumQuestion->forum_answers_count>0) {
                return response()->json([
                    'errorCode' => 1,
                    'message' => 'You can not edit forum once someone answered'
                ]);
            }
            $forumQuestion->question = urldecode($data['question']);
            $forumQuestion->save();
            return response()->json([
                'errorCode' => 0,
                'message' => 'Forum updated successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Forum update failed'
        ]);
    }
}
