<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CountryMaster;
use App\StateMaster;
use Auth;
use File;

class StateMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('states','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $states = StateMaster::with('country')->withCount('districts')->get();
        return view('state_master.list', compact('states'));
    }
    public function add(){
        if(!$this->getPermission('states','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $countries = CountryMaster::get();
    	return view('state_master.add', compact('countries'));
    }
    public function save(Request $request){
        if(!$this->getPermission('states','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $state = new StateMaster;
        $state->country_id  = $request->country_id;
        $state->name = $request->name;
        $state->name_hn = $request->name_hn;
        $state->name_mr = $request->name_mr;
        if($state->save())
           return redirect('/stateMaster')->with('success','State added successful');
    	else
           return redirect('/stateMaster')->with('error','State add failed');
    }
    public function edit($id){
        if(!$this->getPermission('states','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$state = StateMaster::Where('id',$id)->first();
    	if($state) {
            $countries = CountryMaster::get();
            return view('state_master.edit',compact('countries', 'state'));
        }
        return redirect('/stateMaster')->with('error','State not found');

    }
    public function update(Request $request){
        if(!$this->getPermission('states','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$state = StateMaster::Where('id',$request->id)->first();
        if($state) {
            $state->country_id  = $request->country_id;
        	$state->name = $request->name;
            $state->name_hn = $request->name_hn;
            $state->name_mr = $request->name_mr;
        	if($state->save())
               return redirect('/stateMaster')->with('success','State updated successful');
        	else
               return redirect('/stateMaster')->with('error','State update failed');
        }
        return redirect('/stateMaster')->with('error','State not found');
    }
    public function delete($id){
        if(!$this->getPermission('states','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$state = StateMaster::Where('id',$id)->first();
    	if($state) {
            if($state->delete())
               return redirect('/stateMaster')->with('success','State deleted successful');
        	else
               return redirect('/stateMaster')->with('error','State delete failed');
        }
        return redirect('/stateMaster')->with('error','State not found');
    }
    public function getStateByCountry($id){
        $states = StateMaster::Where('country_id',$id)->get();
        return response()->json($states);
    }
}
