<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Requisitions;
use App\VendorItem;
use App\VendorRequisition;
use Auth;
use File;
use Validator;

class RequisitionController extends Controller
{
    public function index(){
        if(!$this->getPermission('requisitions','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if(Auth::user()->privileges==1)
            $requisitions = Requisitions::withCount('enquries')->orderBy('id','desc')->get();
        else if(Auth::user()->privileges==3)
            $requisitions = Requisitions::withCount('enquries')
                ->join('vendor_requisitions','vendor_requisitions.requisition_id','requisitions.id')
                ->where('vendor_requisitions.user_id',Auth::user()->id)
                ->orderBy('vendor_requisitions.id','desc')
                ->get();
    	return view('requisition.list', compact('requisitions'));
    }
    public function add(){
        if(!$this->getPermission('requisitions','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        return view('requisition.add');
    }
    // public function save(Request $request){
    //     $requisition = new Requisitions;
    //     $requisition->name = $request->name;
    //     if($requisition->save())
    //        return redirect('/requisitionMaster')->with('success','Country added successful');
    // 	else
    //        return redirect('/requisitionMaster')->with('error','Country add failed');
    // }
    public function save(Request $request){
        if(!$this->getPermission('requisitions','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required|alpha'
        ]);
        if($validator->fails()){
            return back()->withError($validator->errors())->withInput();       
        }
        dd(1);
        $requisition = new Requisitions;
        $requisition->name = $request->name;
        if($requisition->save())
           return redirect('/requisitionMaster')->with('success','Country added successful');
        else
           return redirect('/requisitionMaster')->with('failed','Country add failed');
    }
    public function edit($id){
        if(!$this->getPermission('requisitions','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$requisition = Requisitions::Where('id',$id)->first();
        return view('requisition.edit',compact('requisition'));
    }
    public function update(Request $request){
        if(!$this->getPermission('requisitions','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$requisition = Requisitions::Where('id',$request->id)->first();
    	$requisition->name = $request->name;
    	if($requisition->save())
           return redirect('/requisitionMaster')->with('success','Country updated successful');
    	else
           return redirect('/requisitionMaster')->with('error','Country update failed');
    }
    public function delete($id){
        if(!$this->getPermission('requisitions','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$requisition = Requisitions::Where('id',$id)->first();
    	if($requisition->delete())
           return redirect('/requisitionMaster')->with('success','Country deleted successful');
    	else
           return redirect('/requisitionMaster')->with('error','Country delete failed');
    }
    public function details($id){
    	if(!$this->getPermission('requisitions','is_read')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $requisition = Requisitions::with('requisition_items.uom')->Where('id',$id)->first();
        if($requisition) {
            $vendor_requisitions = VendorRequisition::with('vendor')->where('requisition_id',$id)->get();
            // dd($items);
            return view('requisition.details',compact('requisition','vendor_requisitions'));
        }
        return redirect('/requisitions')->with('error','Requisition not found');
    }
    public function vendorDetails($id){
        if(!$this->getPermission('requisitions','is_read')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $requisition = Requisitions::with('requisition_items.uom')->Where('id',$id)->first();
        if($requisition) {
            $vendor_requisitions = VendorRequisition::Where('user_id',Auth::user()->id)->pluck('requisition_id')->toArray();
            if(in_array($id, $vendor_requisitions)) {
                $items = VendorItem::with('item_master')->with('vendor_item_attributes')->where('user_id',Auth::user()->id)->where('item_master_id',$requisition->requisition_items[0]->item_master_id)->first();
                // dd($items);
                return view('requisition.vendor_details',compact('requisition','items'));
            }
        }
        return redirect('/requisitions')->with('error','Requisition not found');
    }
}
