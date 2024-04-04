<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Faq;
use Auth;
use File;

class FaqController extends Controller
{
    public function index(){
        if(!$this->getPermission('faqs','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $faqs = Faq::get();
    	return view('faq.list', compact('faqs'));
    }
    public function add(){
        if(!$this->getPermission('faqs','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        return view('faq.add');
    }
    public function save(Request $request){
        if(!$this->getPermission('faqs','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $faq = new Faq;
        $faq->question = $request->question;
        $faq->question_hn = $request->question_hn;
        $faq->question_mr = $request->question_mr;
        $faq->answer = $request->answer;
        $faq->answer_hn = $request->answer_hn;
        $faq->answer_mr = $request->answer_mr;
        if($faq->save())
           return redirect('/faq')->with('success','Faq added successful');
    	else
           return redirect('/faq')->with('error','Faq add failed');
    }
    public function edit($id){
        if(!$this->getPermission('faqs','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$faq = Faq::Where('id',$id)->first();
        if($faq) {
            return view('faq.edit',compact('faq'));
        }
        return redirect('/faq')->with('error','Faq not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('faqs','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$faq = Faq::Where('id',$request->id)->first();
        if($faq) {
        	$faq->question = $request->question;
            $faq->question_hn = $request->question_hn;
            $faq->question_mr = $request->question_mr;
            $faq->answer = $request->answer;
            $faq->answer_hn = $request->answer_hn;
            $faq->answer_mr = $request->answer_mr;
        	if($faq->save())
               return redirect('/faq')->with('success','Faq updated successful');
        	else
               return redirect('/faq')->with('error','Faq update failed');
        }
        return redirect('/faq')->with('error','Faq not found');
    }
    public function delete($id){
    	if(!$this->getPermission('faqs','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$faq = Faq::Where('id',$id)->first();
        if($faq) {
        	if($faq->delete())
               return redirect('/faq')->with('success','Faq deleted successful');
        	else
               return redirect('/faq')->with('error','Faq delete failed');
        }
        return redirect('/faq')->with('error','Faq not found');
    }
    public function apiGetFaq(){
        $header = request()->header('lang');
        $lang = 'question';
        $answer_lang = 'answer';
        $column = 'question';
        if($header=='hn') {
            $lang = 'question_hn as question';
            $answer_lang = 'answer_hn as answer';
            $column = 'question_hn';
        }
        else if($header=='mr') {
            $lang = 'question_mr as question';
            $answer_lang = 'answer_mr as answer';
            $column = 'question_mr';
        }
        $faqs = Faq::whereNotNull($column)->select('id', $lang, $answer_lang)->get();
        if(count($faqs)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $faqs,
                'message' => 'Faq get successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Faq details not available'
        ]);
    }
}
