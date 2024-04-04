<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enquiries;
use Auth;
use File;
use Validator;

class EnquiryController extends Controller
{
    public function index(){
        if(Auth::user()->privileges==1) {
           $enquiries = Enquiries::withCount('purchase_orders')->orderBy('id','desc')->get();
           return view('enquiry.list', compact('enquiries'));
        }
        else if(Auth::user()->privileges==3) {
    	   $enquiries = Enquiries::with('requisition')->withCount('purchase_orders')->where('user_id',Auth::user()->id)->orderBy('id','desc')->get();
    	   return view('enquiry.vendor_list', compact('enquiries'));
        }
    }
    public function add(){
        return view('enquiry.add');
    }
    // public function save(Request $request){
    //     $country = new Enquiries;
    //     $country->name = $request->name;
    //     if($country->save())
    //        return redirect('/countryMaster')->with('success','Country added successful');
    // 	else
    //        return redirect('/countryMaster')->with('error','Country add failed');
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
        $country = new Enquiries;
        $country->name = $request->name;
        if($country->save())
           return redirect('/countryMaster')->with('success','Country added successful');
        else
           return redirect('/countryMaster')->with('failed','Country add failed');
    }
    public function edit($id){
    	$country = Enquiries::Where('id',$id)->first();
        return view('enquiry.edit',compact('country'));
    }
    public function update(Request $request){
    	$country = Enquiries::Where('id',$request->id)->first();
    	$country->name = $request->name;
    	if($country->save())
           return redirect('/countryMaster')->with('success','Country updated successful');
    	else
           return redirect('/countryMaster')->with('error','Country update failed');
    }
    public function delete($id){
    	$country = Enquiries::Where('id',$id)->first();
    	if($country->delete())
           return redirect('/countryMaster')->with('success','Country deleted successful');
    	else
           return redirect('/countryMaster')->with('error','Country delete failed');
    }
}
