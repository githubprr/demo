<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UomMaster;
use Auth;
use File;
use Validator;

class UomMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('uom','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $uoms = UomMaster::withCount('items')->get();
    	return view('uom_master.list', compact('uoms'));
    }
    public function add(){
        if(!$this->getPermission('uom','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        return view('uom_master.add');
    }
    // public function save(Request $request){
    //     $uom = new UomMaster;
    //     $uom->name = $request->name;
    //     if($uom->save())
    //        return redirect('/uomMaster')->with('success','Uom added successful');
    // 	else
    //        return redirect('/uomMaster')->with('error','Uom add failed');
    // }
    public function save(Request $request){
        if(!$this->getPermission('uom','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $input = $request->all();
        $request->validate([
            'uom' => 'required|alpha'
        ]);
        // $validator = Validator::make($input, [
        //     'uom' => 'required|alpha'
        // ]);
        // if($validator->fails()){
        //     // dd($validator->errors());
        //     return back()->with('error',$validator->errors());       
        // }
        // dd(1);
        $uom = new UomMaster;
        $uom->uom = $request->uom;
        $uom->uom_hn = $request->uom_hn;
        $uom->uom_mr = $request->uom_mr;
        if($uom->save())
           return redirect('/uomMaster')->with('success','Uom added successful');
        else
           return redirect('/uomMaster')->with('failed','Uom add failed');
    }
    public function edit($id){
        if(!$this->getPermission('uom','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$uom = UomMaster::Where('id',$id)->first();
        if($uom) {
            return view('uom_master.edit',compact('uom'));
        }
        return redirect('/uomMaster')->with('error','Uom not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('uom','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$uom = UomMaster::Where('id',$request->id)->first();
        if($uom) {
            $uom->uom = $request->uom;
            $uom->uom_hn = $request->uom_hn;
            $uom->uom_mr = $request->uom_mr;
        	if($uom->save())
               return redirect('/uomMaster')->with('success','Uom updated successful');
        	else
               return redirect('/uomMaster')->with('error','Uom update failed');
        }
        return redirect('/uomMaster')->with('error','Uom not found');
    }
    public function delete($id){
    	if(!$this->getPermission('uom','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$uom = UomMaster::Where('id',$id)->first();
    	if($uom) {
        	if($uom->delete())
               return redirect('/uomMaster')->with('success','Uom deleted successful');
        	else
               return redirect('/uomMaster')->with('error','Uom delete failed');
        }
        return redirect('/uomMaster')->with('error','Uom not found');
    }
}
