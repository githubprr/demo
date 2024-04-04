<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FaqMaster;
use Auth;
use File;

class FaqMasterController extends Controller
{
    public function index(){
    	$faqs = FaqMaster::get();
    	return view('faq_master.list', compact('faqs'));
    }
    public function add(){
        return view('faq_master.add');
    }
    public function save(Request $request){
        $faq = new FaqMaster;
        $faq->question = $request->question;
        $faq->answer = $request->answer;
        if($faq->save())
           return redirect('/faqMaster')->with('success','Faq added successful');
    	else
           return redirect('/faqMaster')->with('error','Faq add failed');
    }
    public function edit($id){
    	$faq = FaqMaster::Where('id',$id)->first();
        return view('faq_master.edit',compact('faq'));
    }
    public function update(Request $request){
    	$faq = FaqMaster::Where('id',$request->id)->first();
    	$faq->question = $request->question;
        $faq->answer = $request->answer;
    	if($faq->save())
           return redirect('/faqMaster')->with('success','Faq updated successful');
    	else
           return redirect('/faqMaster')->with('error','Faq update failed');
    }
    public function delete($id){
    	$faq = FaqMaster::Where('id',$id)->first();
    	if($faq->delete())
           return redirect('/faqMaster')->with('success','Faq deleted successful');
    	else
           return redirect('/faqMaster')->with('error','Faq delete failed');
    }
}
