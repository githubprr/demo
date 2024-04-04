<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\QuizMaster;
use Auth;
use File;
use Validator;

class QuizMasterController extends Controller
{
    public function index(){
    	$quizs = QuizMaster::get();
    	return view('quiz_master.list', compact('quizs'));
    }
    public function add(){
        return view('quiz_master.add');
    }
    public function save(Request $request){
        $quiz = new QuizMaster;
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
            }
            if($request->hasFile('img_option_2')){
                $file2= $_FILES['img_option_2']['name'];
                $var2=explode(".",$file2);
                $ext2='.'.end($var2);
                $value2 = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename2 =  $value2.'-2'.$ext2;

                move_uploaded_file($_FILES['img_option_2']['tmp_name'], $filepath.$filename2);
                $quiz->option_2 = $filename2;
            }
            if($request->hasFile('img_option_3')){
                $file3= $_FILES['img_option_3']['name'];
                $var3=explode(".",$file3);
                $ext3='.'.end($var3);
                $value3 = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename3 =  $value1.'-3'.$ext3;

                move_uploaded_file($_FILES['img_option_3']['tmp_name'], $filepath.$filename3);
                $quiz->option_3 = $filename3;
            }
            if($request->hasFile('img_option_4')){
                $file4= $_FILES['img_option_4']['name'];
                $var4=explode(".",$file4);
                $ext4='.'.end($var4);
                $value4 = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename4 =  $value4.'-4'.$ext4;

                move_uploaded_file($_FILES['img_option_4']['tmp_name'], $filepath.$filename4);
                $quiz->option_4 = $filename4;
            }
        }
        else if($request->type==1) {
            $quiz->option_1 = $request->option_1;
            $quiz->option_2 = $request->option_2;
            $quiz->option_3 = $request->option_3;
            $quiz->option_4 = $request->option_4;
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
        
        $quiz->quiz = $request->quiz;
        $quiz->question_type = $request->question_type;
        $quiz->type = $request->type;
        $quiz->answer = $request->answer;
        if($quiz->save())
            return redirect('/quizMaster')->with('success','Quiz added successful');
        else
           return redirect('/quizMaster')->with('error','Quiz add failed');
    }
    public function edit($id){
    	$quiz = QuizMaster::Where('id',$id)->first();
        return view('quiz_master.edit',compact('quiz'));
    }
    public function update(Request $request){
    	$quiz = QuizMaster::Where('id',$request->id)->first();
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
            }
        }
        else if($request->type==1) {
            $quiz->option_1 = $request->option_1;
            $quiz->option_2 = $request->option_2;
            $quiz->option_3 = $request->option_3;
            $quiz->option_4 = $request->option_4;
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
            $quiz->quiz = null;
            
        $quiz->quiz = $request->quiz;
        $quiz->question_type = $request->question_type;
        $quiz->type = $request->type;
        $quiz->answer = $request->answer;
    	if($quiz->save())
           return redirect('/quizMaster')->with('success','Quiz updated successful');
    	else
           return redirect('/quizMaster')->with('error','Quiz update failed');
    }
    public function delete($id){
    	$quiz = QuizMaster::Where('id',$id)->first();
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
           return redirect('/quizMaster')->with('success','Quiz deleted successful');
    	else
           return redirect('/quizMaster')->with('error','Quiz delete failed');
    }
}
