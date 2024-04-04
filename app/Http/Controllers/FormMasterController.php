<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FormMaster;
use Auth;
use File;

class FormMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('form','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $forms = FormMaster::withCount('items')->get();
    	return view('form_master.list', compact('forms'));
    }
    public function add(){
        if(!$this->getPermission('form','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        return view('form_master.add');
    }
    public function save(Request $request){
        if(!$this->getPermission('form','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $form = new FormMaster;
        $form->title = $request->title;
        $form->title_hn = $request->title_hn;
        $form->title_mr = $request->title_mr;
        if($form->save())
           return redirect('/formMaster')->with('success','Form added successful');
    	else
           return redirect('/formMaster')->with('error','Form add failed');
    }
    public function edit($id){
        if(!$this->getPermission('form','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$form = FormMaster::Where('id',$id)->first();
        if($form) {
            return view('form_master.edit',compact('form'));
        }
        return redirect('/formMaster')->with('error','Form not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('form','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$form = FormMaster::Where('id',$request->id)->first();
        if($form) {
        	$form->title = $request->title;
            $form->title_hn = $request->title_hn;
            $form->title_mr = $request->title_mr;
        	if($form->save())
               return redirect('/formMaster')->with('success','Form updated successful');
        	else
               return redirect('/formMaster')->with('error','Form update failed');
        }
        return redirect('/formMaster')->with('error','Form not found');
    }
    public function delete($id){
    	if(!$this->getPermission('form','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$form = FormMaster::Where('id',$id)->first();
        if($form) {
        	if($form->delete())
               return redirect('/formMaster')->with('success','Form deleted successful');
        	else
               return redirect('/formMaster')->with('error','Form delete failed');
        }
        return redirect('/formMaster')->with('error','Form not found');
    }
}
