<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\QuizMaster;
use App\CountryMaster;
use App\StateMaster;
use App\DistrictMaster;
use App\TalukaMaster;
use App\QuizArea;
use App\QuizResult;
use App\QuizQuestionAnswer;
use App\UserQuiz;
use App\Notification;
use Auth;
use File;
use Validator;
use Carbon\Carbon;

class QuizMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('quiz','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $quiz_masters = QuizMaster::withCount('quiz_question_answer')->withCount('quiz_result')->orderBy('id','desc')->get();
        return view('quiz_master.list', compact('quiz_masters'));
    }
    public function add(){
        if(!$this->getPermission('quiz','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $states = StateMaster::all();
        return view('quiz_master.add', compact('states'));
    }
    public function save(Request $request){
        if(!$this->getPermission('quiz','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        // $validator = Validator::make($request->all(), [
        //     'state_id' => 'required|integer',
        //     'district_id' => 'required|integer',
        //     'taluka_id' => 'required|integer'
        // ], [
        //     'state_id.required' => 'State is required',
        //     'district_id.required' => 'District is required',
        //     'taluka_id.required' => 'Taluka is required'
        // ]);
        // if($validator->fails())
        //     return back()->with('validations',$validator->errors());

        $quiz = new QuizMaster;
        $quiz->type = $request->type;
        $quiz->name = $request->name;
        $quiz->name_hn = $request->name_hn;
        $quiz->name_mr = $request->name_mr;
        $quiz->description = $request->description;
        $quiz->description_hn = $request->description_hn;
        $quiz->description_mr = $request->description_mr;
        $quiz->start_date = $request->start_date;
        $quiz->end_date = $request->end_date;
        $quiz->result_date = $request->result_date;
        // $quiz->state_id = $request->state_id;
        // $quiz->district_id = $request->district_id;
        // $quiz->taluka_id = $request->taluka_id;
        $quiz->quiz_level = $request->quiz_level;
        $quiz->winner_nos = $request->winner_nos;
        $quiz->eligible_at = $request->eligible_at;
        if($quiz->save())
           return redirect('/quizMaster')->with('success','Quiz added successful');
        else
           return redirect('/quizMaster')->with('error','Quiz add failed');
    }
    public function edit($id){
        if(!$this->getPermission('quiz','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $quiz = QuizMaster::Where('id',$id)->first();
        if($quiz) {
            $states = StateMaster::all();
            $districts = DistrictMaster::where('state_id',$quiz->state_id)->get();
            $talukas = TalukaMaster::where('district_id',$quiz->district_id)->get();
            return view('quiz_master.edit',compact('quiz','states','districts','talukas'));
        }
        return redirect('/quizMaster')->with('error','Quiz not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('quiz','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        // $validator = Validator::make($request->all(), [
        //     'state_id' => 'required|integer',
        //     'district_id' => 'required|integer',
        //     'taluka_id' => 'required|integer'
        // ], [
        //     'state_id.required' => 'State is required',
        //     'district_id.required' => 'District is required',
        //     'taluka_id.required' => 'Taluka is required'
        // ]);
        // if($validator->fails())
        //     return back()->with('validations',$validator->errors());

        $quiz = QuizMaster::Where('id',$request->id)->first();
        if($quiz) {
            $quiz->name = $request->name;
            $quiz->name_hn = $request->name_hn;
            $quiz->name_mr = $request->name_mr;
            $quiz->description = $request->description;
            $quiz->description_hn = $request->description_hn;
            $quiz->description_mr = $request->description_mr;
            $quiz->start_date = $request->start_date;
            $quiz->end_date = $request->end_date;
            $quiz->result_date = $request->result_date;
            // $quiz->state_id = $request->state_id;
            // $quiz->district_id = $request->district_id;
            // $quiz->taluka_id = $request->taluka_id;
            $quiz->quiz_level = $request->quiz_level;
            $quiz->winner_nos = $request->winner_nos;
            $quiz->eligible_at = $request->eligible_at;
            if($quiz->save())
               return redirect('/quizMaster')->with('success','Quiz updated successful');
            else
               return redirect('/quizMaster')->with('error','Quiz update failed');
       }
        return redirect('/quizMaster')->with('error','Quiz not found');
    }
    public function delete($id){
        if(!$this->getPermission('quiz','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $quiz = QuizMaster::Where('id',$id)->first();
        if($quiz) {
            if($quiz->delete())
               return redirect('/quizMaster')->with('success','Quiz deleted successful');
            else
               return redirect('/quizMaster')->with('error','Quiz delete failed');
        }
        return redirect('/quizMaster')->with('error','Quiz not found');
    }
    public function apiGetQuizs(){
        $header = request()->header('lang');
        $name = 'name';
        $description = 'description';
        $column = 'name';
        if($header=='hn') {
            $name = 'name_hn as name';
            $description = 'description_hn as description';
            $column = 'name_hn';
        }
        else if($header=='mr') {
            $name = 'name_mr as name';
            $description = 'description_mr as description';
            $column = 'name_mr';
        }
        $quizs = QuizMaster::whereNotNull($column)->where('end_date','>=',Carbon::today()->toDateString())->select('id', 'type', $name, $description, 'start_date', 'end_date', 'result_date')->orderBy('id','desc')->get();
        if($quizs) {
            return response()->json([
                'errorCode' => 0,
                'data' => $quizs,
                'message' => 'Get quizs successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get quizs failed'
        ]);
    }
    public function quizArea($id){
        if(!$this->getPermission('quiz','is_read')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $quiz_master_id = $id;
        $quiz_master = QuizMaster::Where('id',$id)->first();
        if($quiz_master->quiz_level==1) {
            $quizAreaStates = QuizArea::with('state')->where('quiz_level',$quiz_master->quiz_level)->where('quiz_master_id',$quiz_master_id)->get();
            return view('quiz_master.quizstatelist',compact('quizAreaStates','quiz_master_id','quiz_master'));
        }
        else if($quiz_master->quiz_level==2) {
            $quizAreaDistricts = QuizArea::with('state')->with('district')->where('quiz_level',$quiz_master->quiz_level)->where('quiz_master_id',$quiz_master_id)->get();
            return view('quiz_master.quizdistrictlist',compact('quizAreaDistricts','quiz_master_id','quiz_master'));
        }
        else if($quiz_master->quiz_level==3) {
            $quizAreaTalukas = QuizArea::with('state')->with('district')->where('quiz_level',$quiz_master->quiz_level)->where('quiz_master_id',$quiz_master_id)->get();
            return view('quiz_master.quiztalukalist',compact('quizAreaTalukas','quiz_master_id','quiz_master'));
        }
    }
    public function quizAreaAdd($id){
        $quiz_master_id = $id;
        $quiz = QuizMaster::Where('id',$id)->first();
        if($quiz->quiz_level==1) {
            $countries = CountryMaster::all();
            return view('quiz_master.quizstate',compact('countries','quiz_master_id'));
        }
        else if($quiz->quiz_level==2) {
            $states = StateMaster::get();
            return view('quiz_master.quizdistrict',compact('states','quiz_master_id'));
        }
        else if($quiz->quiz_level==3) {
            $districts = DistrictMaster::get();
            return view('quiz_master.quiztaluka',compact('districts','quiz_master_id'));
        }
    }
    public function getStateByCountryForQuiz($id, $quiz_master_id){
        $quiz_master = QuizMaster::Where('id',$quiz_master_id)->first();
        $states = StateMaster::Where('country_id',$id)->orderBy('name','asc')->get();
        //$quizAreaStatesExclude = QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('quiz_master_id','<>',$quiz_master_id)->pluck('state_id');
        $quizAreaStates = QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('quiz_master_id',$quiz_master_id)->pluck('state_id');
        $data = array();
        foreach ($states as $key => $value) {
            $state['id'] = $value->id;
            $state['name'] = $value->name;
            //$state['disabled'] = in_array($value->id, $quizAreaStatesExclude->toArray())?true:false;
            $state['checked'] = in_array($value->id, $quizAreaStates->toArray())?true:false;
            array_push($data, $state);
        }
        return response()->json($data);
    }
    public function saveQuizAreaStates(Request $request){
        $quiz_master = QuizMaster::Where('id',$request->quiz_master_id)->first();
        $quizAreaStates = QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('quiz_master_id',$request->quiz_master_id)->pluck('state_id');
        $data = array();
        $keep_ids = array();
        $delete_ids = array();
        if(isset($request->states)) {
            foreach ($request->states as $key => $state) {
                if(!in_array($state, $quizAreaStates->toArray())) {
                    $quizUserArea['quiz_master_id'] = $request->quiz_master_id;
                    $quizUserArea['quiz_level'] = $quiz_master->quiz_level;
                    $quizUserArea['state_id'] = $state;
                    array_push($data, $quizUserArea);
                }
                else {
                    array_push($keep_ids, $state);
                }
            }
            $differenceArray = array_diff($quizAreaStates->toArray(), $keep_ids);
            QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('quiz_master_id',$request->quiz_master_id)->whereIn('district_id',$differenceArray)->delete();
        }
        else {
            QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('country_id',$request->country_id)->where('quiz_master_id',$request->quiz_master_id)->delete();
        }
        QuizArea::insert($data);
        return redirect('/quizMaster')->with('success','Quiz area updated successful');
    }
    public function getDistrictByStateForQuiz($id, $quiz_master_id){
        $quiz_master = QuizMaster::Where('id',$quiz_master_id)->first();
        $districts = DistrictMaster::Where('state_id',$id)->orderBy('name','asc')->get();
        //$quizAreaDistrictsExclude = QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('state_id',$id)->where('quiz_master_id','<>',$quiz_master_id)->pluck('district_id');
        $quizAreaDistricts = QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('state_id',$id)->where('quiz_master_id',$quiz_master_id)->pluck('district_id');
        $data = array();
        foreach ($districts as $key => $value) {
            $district['id'] = $value->id;
            $district['name'] = $value->name;
            //$district['disabled'] = in_array($value->id, $quizAreaDistrictsExclude->toArray())?true:false;
            $district['checked'] = in_array($value->id, $quizAreaDistricts->toArray())?true:false;
            array_push($data, $district);
        }
        return response()->json($data);
    }
    public function saveQuizAreaDistricts(Request $request){
        $quiz_master = QuizMaster::Where('id',$request->quiz_master_id)->first();
        $quizUserAreaDistricts = QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('state_id',$request->state_id)->where('quiz_master_id',$request->quiz_master_id)->pluck('district_id');
        $data = array();
        $keep_ids = array();
        $delete_ids = array();
        if(isset($request->districts)) {
            foreach ($request->districts as $key => $district) {
                if(!in_array($district, $quizUserAreaDistricts->toArray())) {
                    $quizUserArea['quiz_master_id'] = $request->quiz_master_id;
                    $quizUserArea['quiz_level'] = $quiz_master->quiz_level;
                    $quizUserArea['state_id'] = $request->state_id;
                    $quizUserArea['district_id'] = $district;
                    array_push($data, $quizUserArea);
                }
                else {
                    array_push($keep_ids, $district);
                }
            }
            $differenceArray = array_diff($quizUserAreaDistricts->toArray(), $keep_ids);
            QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('state_id',$request->state_id)->where('quiz_master_id',$request->quiz_master_id)->whereIn('district_id',$differenceArray)->delete();
        }
        else {
            QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('state_id',$request->state_id)->where('quiz_master_id',$request->quiz_master_id)->delete();
        }
        QuizArea::insert($data);
        return redirect('/quizMaster')->with('success','Quiz area updated successful');
    }
    public function getTalukaByDistrictForQuiz($id, $quiz_master_id){
        $quiz_master = QuizMaster::Where('id',$quiz_master_id)->first();
        $talukas = TalukaMaster::Where('district_id',$id)->orderBy('name','asc')->get();
        //$quizAreaTalukasExclude = QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('district_id',$id)->where('quiz_master_id','<>',$quiz_master_id)->pluck('taluka_id');
        $quizAreaTalukas = QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('district_id',$id)->where('quiz_master_id',$quiz_master_id)->pluck('taluka_id');
        $data = array();
        foreach ($talukas as $key => $value) {
            $taluka['id'] = $value->id;
            $taluka['name'] = $value->name;
            //$taluka['disabled'] = in_array($value->id, $quizAreaTalukasExclude->toArray())?true:false;
            $taluka['checked'] = in_array($value->id, $quizAreaTalukas->toArray())?true:false;
            array_push($data, $taluka);
        }
        return response()->json($data);
    }
    public function saveQuizAreaTalukas(Request $request){
        $quiz_master = QuizMaster::Where('id',$request->quiz_master_id)->first();
        $quizAreaTalukas = QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('district_id',$request->district_id)->where('quiz_master_id',$request->quiz_master_id)->pluck('taluka_id');
        $data = array();
        $keep_ids = array();
        $delete_ids = array();
        if(isset($request->talukas)) {
            foreach ($request->talukas as $key => $taluka) {
                if(!in_array($taluka, $quizAreaTalukas->toArray())) {
                    $quizUserArea['quiz_master_id'] = $request->quiz_master_id;
                    $quizUserArea['quiz_level'] = $quiz_master->quiz_level;
                    $quizUserArea['district_id'] = $request->district_id;
                    $quizUserArea['taluka_id'] = $taluka;
                    array_push($data, $quizUserArea);
                }
                else {
                    array_push($keep_ids, $taluka);
                }
            }
            $differenceArray = array_diff($quizAreaTalukas->toArray(), $keep_ids);
            QuizArea::where('quiz_level',$quiz_master->quiz_level)->where('district_id',$request->district_id)->where('quiz_master_id',$request->quiz_master_id)->whereIn('taluka_id',$differenceArray)->delete();
        }
        else {
            QuizArea::where('quiz_level',$user->quiz_level)->where('district_id',$request->district_id)->where('quiz_master_id',$request->quiz_master_id)->delete();
        }
        QuizArea::insert($data);
        return redirect('/quizMaster')->with('success','Quiz area updated successful');
    }
    public function quizResult($id,$type=1){
        $id = $id;
        $type = $type;
        $quiz_master = QuizMaster::where('id',$id)->first();
        $quiz_result = QuizResult::with('user')->where('quiz_master_id',$id)->where('type',$type)->orderBy('percentage','desc')->get();
        return view('quiz_master.quiz_result', compact('quiz_master','quiz_result','id','type'));
    }
    public function quizResultUser($id,$type,$user_id){
        $quiz_master = QuizMaster::where('id',$id)->first();
        $user_quiz = QuizQuestionAnswer::select('quiz_question_answer.id','quiz_question_answer.quiz','quiz_question_answer.question_type','quiz_question_answer.quiz_image','quiz_question_answer.type','quiz_question_answer.option_1','quiz_question_answer.option_2','quiz_question_answer.option_3','quiz_question_answer.option_4','quiz_question_answer.answer','user_quiz.user_answer','user_quiz.correct_answer')
            ->leftJoin('user_quiz','quiz_question_answer.id','user_quiz.quiz_question_answer_id')
            ->where('quiz_question_answer.quiz_master_id',$id)
            ->where('quiz_question_answer.type',$type)
            ->where(function($query) use ($user_id){
                $query->where('user_quiz.user_id',$user_id)
                ->orWhereNull('user_quiz.user_id');
            })
            ->get();
        return view('quiz_master.user_quiz', compact('quiz_master','user_quiz'));
    }
    public function apiQuizResult($id,$type=1){
        // $quiz_result = QuizResult::with('quiz_master')->with('user')->where('quiz_master_id',$id)->where('type',$type)->orderBy('percentage','desc')->get();
        $quiz_result = QuizResult::with('quiz_master')->with('user')->where('quiz_master_id',$id)->where('type',$type)->orderBy('rank','asc')->get();
        if(count($quiz_result)) {
            $quiz_result = $quiz_result->map(function($value, $key) {
                if($value->percentage==null)
                    $value->percentage = 0;
                else
                    $value->percentage = round($value->percentage,0);
                return $value;
            });
            return response()->json([
                'errorCode' => 0,
                'data' => $quiz_result,
                'message' => 'Get quiz result successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get quiz result failed'
        ]);
    }
    public function apiQuizResultUser($id,$type){
        $user_id = auth()->guard('api')->user()->id;
        $user_quiz = QuizQuestionAnswer::select('quiz_question_answer.id','quiz_question_answer.quiz','quiz_question_answer.question_type','quiz_question_answer.quiz_image','quiz_question_answer.type','quiz_question_answer.option_1','quiz_question_answer.option_2','quiz_question_answer.option_3','quiz_question_answer.option_4','quiz_question_answer.answer','user_quiz.user_answer','user_quiz.correct_answer')
            ->leftJoin('user_quiz','quiz_question_answer.id','user_quiz.quiz_question_answer_id')
            ->where('quiz_question_answer.quiz_master_id',$id)
            ->where('quiz_question_answer.type',$type)
            ->where(function($query) use ($user_id){
                $query->where('user_quiz.user_id',$user_id)
                ->orWhereNull('user_quiz.user_id');
            })
            ->get();
        if($user_quiz) {
            return response()->json([
                'errorCode' => 0,
                'data' => $user_quiz,
                'message' => 'Get user quiz successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get user quiz failed'
        ]);
    }
    public function quizSetWinnerold($id){
        $quiz_master = QuizMaster::where('id',$id)->first();
        $top = 10;
        if($quiz_master->winner_nos<=10)
            $top = 10;
        else if($quiz_master->winner_nos<=25)
            $top = 40;
        else if($quiz_master->winner_nos<=50)
            $top = 75;
        else if($quiz_master->winner_nos<=75)
            $top = 90;
        else if($quiz_master->winner_nos<=100)
            $top = 125;
        else if($quiz_master->winner_nos<=150)
            $top = 200;
        else if($quiz_master->winner_nos<=200)
            $top = 300;
        else if($quiz_master->winner_nos<=250)
            $top = 500;
        else if($quiz_master->winner_nos<=500)
            $top = 800;
        else if($quiz_master->winner_nos<=750)
            $top = 1000;
        else if($quiz_master->winner_nos<=1000)
            $top = 1300;
        else
            $top = 2000;

        $quiz_result = QuizResult:://with('quiz_master')
            // ->with('user')
            where('quiz_master_id',$id)
            ->where('percentage','>=',(isset($quiz_master->eligible_at) && $quiz_master->eligible_at>0)?$quiz_master->eligible_at:50)
            // ->orderBy('percentage','desc')
            ->take($top)
            ->inRandomOrder()
            ->get();
        dd($quiz_result);
        if($quiz_result) {
            return response()->json([
                'errorCode' => 0,
                'data' => $quiz_result,
                'message' => 'Get quiz result successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get quiz result failed'
        ]);
    }
    public function quizSetWinner(){
        $today = date('Y-m-d');
        $quiz_master = QuizMaster::where('result_date',$today)->where('is_done',0)->get();
        // $quiz_master = QuizMaster::where('id',3)->get();
        if(sizeof($quiz_master)>0) {
            foreach ($quiz_master as $key => $quiz) {
                $quiz_result = QuizResult::
                    where('quiz_master_id',$quiz->id)
                    ->where('percentage','>=',$quiz->eligible_at)
                    ->take($quiz->winner_nos)
                    ->orderBy('percentage','desc')
                    ->get();
                if(sizeof($quiz_result)>0) {
                    foreach ($quiz_result as $key => $result) {
                        QuizResult::where('id',$result->id)->update(['rank' => $key+1]);
                    }
                    QuizMaster::where('id',$quiz->id)->update(['is_done' => 1]);
                    $quizArea = QuizArea::where('quiz_master_id',$quiz->id)->get();
                    if(sizeof($quizArea)>0) {
                        foreach ($quizArea as $key => $area) {
                            $notification = new Notification;
                            $notification->notification = $quiz->name.' quiz result is declared';
                            $notification->notification_hn = $quiz->name_hn.' क्विज का रिजल्ट घोषित';
                            $notification->notification_mr = $quiz->name_mr.' प्रश्नमंजुषा निकाल जाहीर केला आहे';
                            if($quiz->quiz_level==1)
                                $notification->state_id = $area->state_id;
                            else if($quiz->quiz_level==2) {
                                $district = DistrictMaster::where('id',$area->district_id)->first();
                                $notification->state_id = $district->state_id;
                                $notification->district_id = $area->district_id;
                            }
                            else if($quiz->quiz_level==3) {
                                $taluka = TalukaMaster::where('id',$area->taluka_id)->first();
                                $district = DistrictMaster::where('id',$taluka->district_id)->first();
                                $notification->state_id = $district->state_id;
                                $notification->district_id = $taluka->district_id;
                                $notification->taluka_id = $area->taluka_id;
                            }
                            $notification->save();
                        }
                    }
                }
            }
        }
    }
}
