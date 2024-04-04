<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\QuizQuestionAnswer;
use App\UserQuiz;
use App\QuizResult;
use App\QuizMaster;
use App\PointMaster;
use App\PointHistory;
use Auth;
use File;
use Excel;
use Validator;

class QuizQuestionAnswerController extends Controller
{
    public function index($id){
        if(!$this->getPermission('quiz','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $quiz_master_id = $id;
        $quiz_master = QuizMaster::where('id',$id)->first();
        if($quiz_master) {
        	$quizs = QuizQuestionAnswer::where('quiz_master_id',$id)->get();
        	return view('quiz_question_answer.list', compact('quizs','quiz_master_id','quiz_master'));
        }
        return redirect('/quizMaster')->with('error','Quiz not found');
    }
    public function add($id){
        if(!$this->getPermission('quiz','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $quiz_master_id = $id;
        $quiz_master = QuizMaster::where('id',$id)->select('type')->first();
        if($quiz_master) {
            $quiz_master_type = $quiz_master->type;
            return view('quiz_question_answer.add', compact('quiz_master_id','quiz_master_type'));
        }
        return redirect('/quizMaster')->with('error','Quiz not found');
    }
    public function save(Request $request){
        if(!$this->getPermission('quiz','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $quiz = new QuizQuestionAnswer;
        if($request->type==2) {
            $filepath = public_path('uploads/quiz/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);
            if($request->hasFile('img_option_1')){
                $file1= $_FILES['img_option_1']['name'];
                $var1=explode(".",$file1);
                $ext1='.'.end($var1);
                $value1 = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename1 =  $value1.'-1'.$ext1;

                move_uploaded_file($_FILES['img_option_1']['tmp_name'], $filepath.$filename1);
                $quiz->option_1 = $filename1;
                $quiz->option_1_hn = $filename1;
                $quiz->option_1_mr = $filename1;
            }
            if($request->hasFile('img_option_2')){
                $file2= $_FILES['img_option_2']['name'];
                $var2=explode(".",$file2);
                $ext2='.'.end($var2);
                $value2 = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename2 =  $value2.'-2'.$ext2;

                move_uploaded_file($_FILES['img_option_2']['tmp_name'], $filepath.$filename2);
                $quiz->option_2 = $filename2;
                $quiz->option_2_hn = $filename2;
                $quiz->option_2_mr = $filename2;
            }
            if($request->hasFile('img_option_3')){
                $file3= $_FILES['img_option_3']['name'];
                $var3=explode(".",$file3);
                $ext3='.'.end($var3);
                $value3 = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename3 =  $value1.'-3'.$ext3;

                move_uploaded_file($_FILES['img_option_3']['tmp_name'], $filepath.$filename3);
                $quiz->option_3 = $filename3;
                $quiz->option_3_hn = $filename3;
                $quiz->option_3_mr = $filename3;
            }
            if($request->hasFile('img_option_4')){
                $file4= $_FILES['img_option_4']['name'];
                $var4=explode(".",$file4);
                $ext4='.'.end($var4);
                $value4 = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename4 =  $value4.'-4'.$ext4;

                move_uploaded_file($_FILES['img_option_4']['tmp_name'], $filepath.$filename4);
                $quiz->option_4 = $filename4;
                $quiz->option_4_hn = $filename4;
                $quiz->option_4_mr = $filename4;
            }
        }
        else if($request->type==1) {
            $quiz->option_1 = $request->option_1;
            $quiz->option_1_hn = $request->option_1_hn;
            $quiz->option_1_mr = $request->option_1_mr;
            $quiz->option_2 = $request->option_2;
            $quiz->option_2_hn = $request->option_2_hn;
            $quiz->option_2_mr = $request->option_2_mr;
            $quiz->option_3 = $request->option_3;
            $quiz->option_3_hn = $request->option_3_hn;
            $quiz->option_3_mr = $request->option_3_mr;
            $quiz->option_4 = $request->option_4;
            $quiz->option_4_hn = $request->option_4_hn;
            $quiz->option_4_mr = $request->option_4_mr;
        }
        if($request->question_type==2) {
            $filepath = public_path('uploads/quiz/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);
            if($request->hasFile('quiz_image')){
                $filequiz= $_FILES['quiz_image']['name'];
                $varquiz=explode(".",$filequiz);
                $extquiz='.'.end($varquiz);
                $valuequiz = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filenamequiz =  $valuequiz.'-quiz'.$extquiz;

                move_uploaded_file($_FILES['quiz_image']['tmp_name'], $filepath.$filenamequiz);
                $quiz->quiz_image = $filenamequiz;
            }
        }
        else
            $quiz->quiz_image = null;
        
        $quiz->quiz_master_id = $request->quiz_master_id;
        $quiz->quiz = $request->quiz;
        $quiz->quiz_hn = $request->quiz_hn;
        $quiz->quiz_mr = $request->quiz_mr;
        $quiz->question_type = $request->question_type;
        $quiz->type = $request->type;
        $quiz->answer = $request->answer;
        if($quiz->save())
            return redirect('/quizQuestionAnswer/'.$request->quiz_master_id)->with('success','Quiz added successful');
        else
           return redirect('/quizQuestionAnswer/'.$request->quiz_master_id)->with('error','Quiz add failed');
    }
    public function edit($id){
        if(!$this->getPermission('quiz','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$quiz = QuizQuestionAnswer::Where('id',$id)->first();
        if($quiz) {
            $quiz_master = QuizMaster::where('id',$quiz->quiz_master_id)->select('type')->first();
            $quiz_master_type = $quiz_master->type;
            return view('quiz_question_answer.edit_'.$quiz_master_type,compact('quiz','quiz_master_type'));
        }
        return redirect('/quizMaster')->with('error','Quiz answer not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('quiz','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$quiz = QuizQuestionAnswer::Where('id',$request->id)->first();
        if($quiz) {
        	if($request->type==2) {
                $filepath = public_path('uploads/quiz/');

                if(!File::isDirectory($filepath))
                    File::makeDirectory($filepath, 0777, true, true);
                if($request->hasFile('img_option_1')){
                    $image_path1 = public_path() . '/uploads/quiz/'.$quiz->option_1;
                    if(File::exists($image_path1)) {
                        File::delete($image_path1);
                    }
                    $file1= $_FILES['img_option_1']['name'];
                    $var1=explode(".",$file1);
                    $ext1='.'.end($var1);
                    $value1 = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                    $filename1 =  $value1.'-1'.$ext1;

                    move_uploaded_file($_FILES['img_option_1']['tmp_name'], $filepath.$filename1);
                    $quiz->option_1 = $filename1;
                    $quiz->option_1_hn = $filename1;
                    $quiz->option_1_mr = $filename1;
                }
                if($request->hasFile('img_option_2')){
                    $image_path2 = public_path() . '/uploads/quiz/'.$quiz->option_2;
                    if(File::exists($image_path2)) {
                        File::delete($image_path2);
                    }
                    $file2= $_FILES['img_option_2']['name'];
                    $var2=explode(".",$file2);
                    $ext2='.'.end($var2);
                    $value2 = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                    $filename2 =  $value2.'-2'.$ext2;

                    move_uploaded_file($_FILES['img_option_2']['tmp_name'], $filepath.$filename2);
                    $quiz->option_2 = $filename2;
                    $quiz->option_2_hn = $filename2;
                    $quiz->option_2_mr = $filename2;
                }
                if($request->hasFile('img_option_3')){
                    $image_path3 = public_path() . '/uploads/quiz/'.$quiz->option_3;
                    if(File::exists($image_path3)) {
                        File::delete($image_path3);
                    }
                    $file3= $_FILES['img_option_3']['name'];
                    $var3=explode(".",$file3);
                    $ext3='.'.end($var3);
                    $value3 = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                    $filename3 =  $value1.'-3'.$ext3;

                    move_uploaded_file($_FILES['img_option_3']['tmp_name'], $filepath.$filename3);
                    $quiz->option_3 = $filename3;
                    $quiz->option_3_hn = $filename3;
                    $quiz->option_3_mr = $filename3;
                }
                if($request->hasFile('img_option_4')){
                    $image_path4 = public_path() . '/uploads/quiz/'.$quiz->option_4;
                    if(File::exists($image_path4)) {
                        File::delete($image_path4);
                    }
                    $file4= $_FILES['img_option_4']['name'];
                    $var4=explode(".",$file4);
                    $ext4='.'.end($var4);
                    $value4 = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                    $filename4 =  $value4.'-4'.$ext4;

                    move_uploaded_file($_FILES['img_option_4']['tmp_name'], $filepath.$filename4);
                    $quiz->option_4 = $filename4;
                    $quiz->option_4_hn = $filename4;
                    $quiz->option_4_mr = $filename4;
                }
            }
            else if($request->type==1) {
                $quiz->option_1 = $request->option_1;
                $quiz->option_1_hn = $request->option_1_hn;
                $quiz->option_1_mr = $request->option_1_mr;
                $quiz->option_2 = $request->option_2;
                $quiz->option_2_hn = $request->option_2_hn;
                $quiz->option_2_mr = $request->option_2_mr;
                $quiz->option_3 = $request->option_3;
                $quiz->option_3_hn = $request->option_3_hn;
                $quiz->option_3_mr = $request->option_3_mr;
                $quiz->option_4 = $request->option_4;
                $quiz->option_4_hn = $request->option_4_hn;
                $quiz->option_4_mr = $request->option_4_mr;
            }
            if($request->question_type==2) {
                $filepath = public_path('uploads/quiz/');

                if(!File::isDirectory($filepath))
                    File::makeDirectory($filepath, 0777, true, true);
                if($request->hasFile('quiz_image')){
                    $image_pathquiz = public_path() . '/uploads/quiz/'.$quiz->quiz_image;
                    if(File::exists($image_pathquiz)) {
                        File::delete($image_pathquiz);
                    }
                    $filequiz= $_FILES['quiz_image']['name'];
                    $varquiz=explode(".",$filequiz);
                    $extquiz='.'.end($varquiz);
                    $valuequiz = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                    $filenamequiz =  $valuequiz.'-quiz'.$extquiz;

                    move_uploaded_file($_FILES['quiz_image']['tmp_name'], $filepath.$filenamequiz);
                    $quiz->quiz_image = $filenamequiz;
                }
            }
            else if($request->type==1)
                $quiz->quiz_image = null;
                
            $quiz->quiz = $request->quiz;
            $quiz->quiz_hn = $request->quiz_hn;
            $quiz->quiz_mr = $request->quiz_mr;
            $quiz->question_type = $request->question_type;
            $quiz->type = $request->type;
            $quiz->answer = $request->answer;
        	if($quiz->save())
               return redirect('/quizQuestionAnswer/'.$quiz->quiz_master_id)->with('success','Quiz updated successful');
        	else
               return redirect('/quizQuestionAnswer/'.$quiz->quiz_master_id)->with('error','Quiz update failed');
        }
        return redirect('/quizMaster')->with('error','Quiz answer not found');
    }
    public function delete($id){
        if(!$this->getPermission('quiz','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$quiz = QuizQuestionAnswer::Where('id',$id)->first();
        if($quiz) {
            if($quiz->question_type==2 && isset($quiz->quiz_image)) {
                $image_pathquiz = public_path() . '/uploads/quiz/'.$quiz->quiz_image;
                if(File::exists($image_pathquiz)) {
                    File::delete($image_pathquiz);
                }
            }
            $image_path1 = public_path() . '/uploads/quiz/'.$quiz->option_1;
            if(File::exists($image_path1)) {
                File::delete($image_path1);
            }
            $image_path2 = public_path() . '/uploads/quiz/'.$quiz->option_2;
            if(File::exists($image_path2)) {
                File::delete($image_path2);
            }
            $image_path3 = public_path() . '/uploads/quiz/'.$quiz->option_3;
            if(File::exists($image_path3)) {
                File::delete($image_path3);
            }
            $image_path4 = public_path() . '/uploads/quiz/'.$quiz->option_4;
            if(File::exists($image_path4)) {
                File::delete($image_path4);
            }
        	if($quiz->delete())
               return redirect('/quizQuestionAnswer/'.$quiz->quiz_master_id)->with('success','Quiz deleted successful');
        	else
               return redirect('/quizQuestionAnswer/'.$quiz->quiz_master_id)->with('error','Quiz delete failed');
           }
        return redirect('/quizMaster')->with('error','Quiz answer not found');
    }
    public function apiQuizByType($quiz_id, $type){
        $user = auth()->guard('api')->user();
        $user_quiz = UserQuiz::where('quiz_master_id',$quiz_id)->where('type',$type)->where('user_id', $user->id)->exists();
        if($user_quiz) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'You have already played this quiz'
            ]);
        }
        $isWalletPoint = $this->canDoActivity('quiz');
        if(!$isWalletPoint) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'You dont have enough points in your wallet'
            ]);
        }

        $header = request()->header('lang');
        $quiz = 'quiz';
        $option_1 = 'option_1';
        $option_2 = 'option_2';
        $option_3 = 'option_3';
        $option_4 = 'option_4';
        $column = 'quiz';
        if($header=='hn') {
            $quiz = 'quiz_hn as quiz';
            $option_1 = 'option_1_hn as option_1';
            $option_2 = 'option_2_hn as option_2';
            $option_3 = 'option_3_hn as option_3';
            $option_4 = 'option_4_hn as option_4';
            $column = 'quiz_hn';
        }
        else if($header=='mr') {
            $quiz = 'quiz_mr as quiz';
            $option_1 = 'option_1_mr as option_1';
            $option_2 = 'option_2_mr as option_2';
            $option_3 = 'option_3_mr as option_3';
            $option_4 = 'option_4_mr as option_4';
            $column = 'quiz_mr';
        }
        $quizs = QuizQuestionAnswer::whereNotNull($column)->select('id', 'question_type', $quiz, 'quiz_image', 'type', $option_1, $option_2, $option_3, $option_4, 'answer')->where('quiz_master_id',$quiz_id)->where('type',$type)->get();
        if($quizs) {
            return response()->json([
                'errorCode' => 0,
                'data' => $quizs,
                'message' => 'Get quiz questions successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get quiz questions failed'
        ]);
    }
    public function apiQuizSubmitold(Request $request){
        $user = auth()->guard('api')->user();

        $quiz_question_answers = QuizQuestionAnswer::where('quiz_master_id',$request->quiz_master_id)->where('type',$request->type)->get();
        foreach ($request->question_answer as $key => $value) {
            $quiz_question_answer = $quiz_question_answers->where('id',$key)->first();
            $user_quiz = new UserQuiz();
            $user_quiz->quiz_master_id = $request->quiz_master_id;
            $user_quiz->type = $request->type;
            $user_quiz->user_id = $user->id;
            $user_quiz->quiz_question_answer_id = $key;
            $user_quiz->user_answer = $value;
            $user_quiz->correct_answer = ($quiz_question_answer->answer==$value)?1:0;
            $user_quiz->save();
        }
        
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
            'message' => 'Quiz submitted successful'
        ]);
    }
    public function apiQuizSubmit(Request $request){
        $user = auth()->guard('api')->user();

        // $quiz_question_answers = QuizQuestionAnswer::where('quiz_master_id',$request->quiz_master_id)->where('type',$request->type)->get();
        // foreach ($request->question_answer as $key => $value) {
        //     $quiz_question_answer = $quiz_question_answers->where('id',$key)->first();
        //     $user_quiz = new UserQuiz();
        //     $user_quiz->quiz_master_id = $request->quiz_master_id;
        //     $user_quiz->type = $request->type;
        //     $user_quiz->user_id = $user->id;
        //     $user_quiz->quiz_question_answer_id = $key;
        //     $user_quiz->user_answer = $value;
        //     $user_quiz->correct_answer = ($quiz_question_answer->answer==$value)?1:0;
        //     $user_quiz->save();
        // }

        $quiz_question_answers = QuizQuestionAnswer::where('quiz_master_id',$request->quiz_master_id)->where('type',$request->type)->get();
        $ques = explode(',', $request->questions);
        $anss = explode(',', $request->answers);
        $correct_answer_count = 0;
        foreach ($ques as $key => $value) {
            $quiz_question_answer = $quiz_question_answers->where('id',$value)->first();
            if($quiz_question_answer) {
                $user_quiz = new UserQuiz();
                $user_quiz->quiz_master_id = $request->quiz_master_id;
                $user_quiz->type = $request->type;
                $user_quiz->user_id = $user->id;
                $user_quiz->quiz_question_answer_id = $value;
                $user_quiz->user_answer = $anss[$key];
                $user_quiz->correct_answer = ($quiz_question_answer->answer==$anss[$key])?1:0;
                $user_quiz->save();

                if($quiz_question_answer->answer==$anss[$key])
                    $correct_answer_count++;
            }
            $quiz_question_answer = null;
        }

        $quiz_result = new QuizResult;
        $quiz_result->quiz_master_id = $request->quiz_master_id;
        $quiz_result->type = $request->type;
        $quiz_result->user_id = $user->id;
        $quiz_result->quiz_question_count = sizeof($quiz_question_answers);
        $quiz_result->correct_answer_count = $correct_answer_count;
        $quiz_result->percentage = number_format((float)((100/sizeof($quiz_question_answers))*$correct_answer_count), 2, '.', '');
        $quiz_result->save();

        $points_master = PointMaster::where('slug','quiz')->first();
        if($points_master) {
            $wallet_points = $points_master->point;
            $user->wallet_points -= $wallet_points;
            $user->save();

            $message = "Substracted ".$wallet_points." points for playing quiz";
            $pointHistory = new PointHistory;
            $pointHistory->message = $message;
            $pointHistory->points = $wallet_points;
            $pointHistory->user_id = $user->id;
            $pointHistory->save();
        }
        return response()->json([
            'errorCode' => 0,
            'message' => 'Quiz submitted successful'
        ]);
    }
    public function apiGetUserQuiz(){
        $user = auth()->guard('api')->user();
        $header = request()->header('lang');
        $lang = 'name';
        $column = 'name';
        if($header=='hn') {
            $lang = 'name_hn as name';
            $column = 'name_hn';
        }
        else if($header=='mr') {
            $lang = 'name_mr as name';
            $column = 'name_mr';
        }
        $user_quizs = UserQuiz::select('quiz_master.id','user_quiz.type','quiz_master.'.$lang)
            ->join('quiz_master','quiz_master.id','user_quiz.quiz_master_id')
            ->where('user_id',$user->id)
            ->groupBy('quiz_master.id')
            ->groupBy('quiz_master.'.$column)
            ->groupBy('type')
            ->get();
        
        if($user_quizs) {
            return response()->json([
                'errorCode' => 0,
                'data' => $user_quizs,
                'message' => 'Get user quiz successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get user quiz failed'
        ]);
    }
    public function apiQuizReport($quiz_id,$type){
        $user = auth()->guard('api')->user();
        $header = request()->header('lang');
        $lang = 'quiz';
        $option_1 = 'option_1';
        $option_2 = 'option_2';
        $option_3 = 'option_3';
        $option_4 = 'option_4';
        $column = 'quiz';
        if($header=='hn') {
            $lang = 'quiz_hn as quiz';
            $option_1 = 'option_1_hn as option_1';
            $option_2 = 'option_2_hn as option_2';
            $option_3 = 'option_3_hn as option_3';
            $option_4 = 'option_4_hn as option_4';
            $column = 'quiz_hn';
        }
        else if($header=='mr') {
            $lang = 'quiz_mr as quiz';
            $option_1 = 'option_1_mr as option_1';
            $option_2 = 'option_2_mr as option_2';
            $option_3 = 'option_3_mr as option_3';
            $option_4 = 'option_4_mr as option_4';
            $column = 'quiz_mr';
        }
        //$quizs = UserQuiz::with('quiz_question_answer')->where('quiz_master_id',$quiz_id)->get();
        $quizs = UserQuiz::select('quiz_question_answer.id',$lang,'quiz_question_answer.question_type','quiz_question_answer.quiz_image','quiz_question_answer.type','quiz_question_answer.'.$option_1,'quiz_question_answer.'.$option_2,'quiz_question_answer.'.$option_3,'quiz_question_answer.'.$option_4,'quiz_question_answer.answer','user_quiz.user_answer','user_quiz.correct_answer')
            ->join('quiz_question_answer','quiz_question_answer.id','user_quiz.quiz_question_answer_id')
            ->whereNotNull('quiz_question_answer.'.$column)
            ->where('user_quiz.quiz_master_id',$quiz_id)
            ->where('user_quiz.type',$type)
            ->where('user_quiz.user_id',$user->id)
            ->get();
        if($quizs) {
            return response()->json([
                'errorCode' => 0,
                'data' => $quizs,
                'message' => 'Get quiz report successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get quiz report failed'
        ]);
    }
    public function importQuiz(Request $request)
    {
        if(!$this->getPermission('quiz','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $request->validate([
            'quiz_master_id' => 'required',
            'import_file' => 'required'
        ]);

        $path = $request->file('import_file')->getRealPath();
        $data = Excel::load($path)->get();
        if($data->count()){
            foreach ($data as $key => $value) {
                $arr[] = [
                    'quiz_master_id' => $request->quiz_master_id,
                    'question_type' => 1,
                    'type' => 1,
                    'quiz' => $value->quiz,
                    'option_1' => $value->option_1,
                    'option_2' => $value->option_2,
                    'option_3' => $value->option_3,
                    'option_4' => $value->option_4,
                    'quiz_hn' => (isset($value->quiz_hn))?$value->quiz_hn:null,
                    'option_1_hn' => (isset($value->option_1_hn))?$value->option_1_hn:null,
                    'option_2_hn' => (isset($value->option_2_hn))?$value->option_2_hn:null,
                    'option_3_hn' => (isset($value->option_3_hn))?$value->option_3_hn:null,
                    'option_4_hn' => (isset($value->option_4_hn))?$value->option_4_hn:null,
                    'quiz_mr' => (isset($value->quiz_mr))?$value->quiz_mr:null,
                    'option_1_mr' => (isset($value->option_1_mr))?$value->option_1_mr:null,
                    'option_2_mr' => (isset($value->option_2_mr))?$value->option_2_mr:null,
                    'option_3_mr' => (isset($value->option_3_mr))?$value->option_3_mr:null,
                    'option_4_mr' => (isset($value->option_4_mr))?$value->option_4_mr:null,
                    'answer' => 'option_'.$value->answer
                ];
            }

            if(!empty($arr)){
                QuizQuestionAnswer::insert($arr);
            }
        }

        return back()->with('success', 'Quiz import successfully.');
    }
}
