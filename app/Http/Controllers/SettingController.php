<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Settings;
use Auth;
use File;
use Validator;

class SettingController extends Controller
{
    public function index(){
    	if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $settings = Settings::get();
    	return view('setting.list', compact('settings'));
    }
    public function add(){
        return view('setting.add');
    }
    // public function save(Request $request){
    //     $country = new Settings;
    //     $country->name = $request->name;
    //     if($country->save())
    //        return redirect('/settings')->with('success','Setting added successful');
    // 	else
    //        return redirect('/settings')->with('error','Setting add failed');
    // }
    public function save(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required|alpha'
        ]);
        if($validator->fails()){
            return back()->withError($validator->errors())->withInput();       
        }
        dd(1);
        $country = new Settings;
        $country->name = $request->name;
        if($country->save())
           return redirect('/settings')->with('success','Setting added successful');
        else
           return redirect('/settings')->with('failed','Setting add failed');
    }
    public function edit($id){
    	if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $country = Settings::Where('id',$id)->first();
        return view('setting.edit',compact('country'));
    }
    public function update(Request $request){
    	if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $country = Settings::Where('id',$request->id)->first();
    	$country->value = $request->value;
    	if($country->save())
           return redirect('/settings')->with('success','Setting updated successful');
    	else
           return redirect('/settings')->with('error','Setting update failed');
    }
    public function delete($id){
    	if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $country = Settings::Where('id',$id)->first();
    	if($country->delete())
           return redirect('/settings')->with('success','Setting deleted successful');
    	else
           return redirect('/settings')->with('error','Setting delete failed');
    }
}
