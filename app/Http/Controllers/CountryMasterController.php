<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CountryMaster;
use Auth;
use File;
use Validator;

class CountryMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('countries','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $countries = CountryMaster::withCount('states')->get();
    	return view('country_master.list', compact('countries'));
    }
    public function add(){
        if(!$this->getPermission('countries','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        return view('country_master.add');
    }
    // public function save(Request $request){
    //     $country = new CountryMaster;
    //     $country->name = $request->name;
    //     if($country->save())
    //        return redirect('/countryMaster')->with('success','Country added successful');
    // 	else
    //        return redirect('/countryMaster')->with('error','Country add failed');
    // }
    public function save(Request $request){
        if(!$this->getPermission('countries','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $input = $request->all();
        $request->validate([
            'name' => 'required|alpha'
        ]);
        // $validator = Validator::make($input, [
        //     'name' => 'required|alpha'
        // ]);
        // if($validator->fails()){
        //     // dd($validator->errors());
        //     return back()->with('error',$validator->errors());       
        // }
        // dd(1);
        $country = new CountryMaster;
        $country->name = $request->name;
        $country->name_hn = $request->name_hn;
        $country->name_mr = $request->name_mr;
        if($country->save())
           return redirect('/countryMaster')->with('success','Country added successful');
        else
           return redirect('/countryMaster')->with('failed','Country add failed');
    }
    public function edit($id){
        if(!$this->getPermission('countries','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$country = CountryMaster::Where('id',$id)->first();
        if($country) {
            return view('country_master.edit',compact('country'));
        }
        return redirect('/countryMaster')->with('error','Country not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('countries','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$country = CountryMaster::Where('id',$request->id)->first();
        if($country) {
        	$country->name = $request->name;
            $country->name_hn = $request->name_hn;
            $country->name_mr = $request->name_mr;
        	if($country->save())
               return redirect('/countryMaster')->with('success','Country updated successful');
        	else
               return redirect('/countryMaster')->with('error','Country update failed');
        }
        return redirect('/countryMaster')->with('error','Country not found');
    }
    public function delete($id){
    	if(!$this->getPermission('countries','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$country = CountryMaster::Where('id',$id)->first();
        if($country) {
        	if($country->delete())
               return redirect('/countryMaster')->with('success','Country deleted successful');
        	else
               return redirect('/countryMaster')->with('error','Country delete failed');
        }
        return redirect('/countryMaster')->with('error','Country not found');
    }
}
