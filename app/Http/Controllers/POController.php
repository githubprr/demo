<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PurchaseOrder;
use App\Enquiries;
use Auth;
use File;
use Validator;
use PDF;

class POController extends Controller
{
    public function index(){
        if(!$this->getPermission('purchase_order','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $purchase_orders = PurchaseOrder::with('enquiry')->withCount('sales_orders')->get();
    	return view('purchase_order.list', compact('purchase_orders'));
    }
    public function add($id){
        if(!$this->getPermission('purchase_order','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$enquiry = Enquiries::Where('id',$id)->first();
        return view('purchase_order.add', compact('enquiry'));
    }
    // public function save(Request $request){
    //     $country = new PurchaseOrder;
    //     $country->name = $request->name;
    //     if($country->save())
    //        return redirect('/countryMaster')->with('success','Country added successful');
    // 	else
    //        return redirect('/countryMaster')->with('error','Country add failed');
    // }
    public function save(Request $request){
        if(!$this->getPermission('purchase_order','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        // $input = $request->all();
        // $validator = Validator::make($input, [
        //     'name' => 'required|alpha'
        // ]);
        // if($validator->fails()){
        //     return back()->withError($validator->errors())->withInput();       
        // }
        // dd(1);
        $purchase_order = new PurchaseOrder;
        $purchase_order->details = $request->details;
        $purchase_order->date = $request->date;
        $purchase_order->vendor_name = $request->vendor_name;
        $purchase_order->vendor_address = $request->vendor_address;
        $purchase_order->vendor_gst_no = $request->vendor_gst_no;
        $purchase_order->enquiry_id = $request->enquiry_id;
        $purchase_order->customer_name = $request->customer_name;
        $purchase_order->customer_address = $request->customer_address;
        $purchase_order->customer_gst_no = $request->customer_gst_no;
        $purchase_order->purchaser_name = $request->purchaser_name;
        $purchase_order->purchaser_address = $request->purchaser_address;
        $purchase_order->purchaser_gst_no = $request->purchaser_gst_no;
        $purchase_order->terms_conditions = $request->terms_conditions;
        $purchase_order->requisition_id = $request->requisition_id;
        $purchase_order->user_id = Auth::user()->id;
        if($purchase_order->save()) {
            $purchase_order->po_reference_no = 'PO'.$purchase_order->id;
            $purchase_order->save();
            return redirect('/pos')->with('success','Purchase Order added successful');
        }
        else
            return redirect('/pos')->with('failed','Purchase Order add failed');
    }
    public function edit($id){
        if(!$this->getPermission('purchase_order','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $country = PurchaseOrder::Where('id',$id)->first();
        return view('country_master.edit',compact('country'));
    }
    public function update(Request $request){
        if(!$this->getPermission('purchase_order','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$country = PurchaseOrder::Where('id',$request->id)->first();
    	$country->name = $request->name;
    	if($country->save())
           return redirect('/pos')->with('success','Country updated successful');
    	else
           return redirect('/pos')->with('error','Country update failed');
    }
    public function delete($id){
    	if(!$this->getPermission('purchase_order','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$country = PurchaseOrder::Where('id',$id)->first();
    	if($country->delete())
           return redirect('/pos')->with('success','Country deleted successful');
    	else
           return redirect('/pos')->with('error','Country delete failed');
    }
    public function download($id)
    {
        $poref = $id;
        // $data = PurchaseOrder::select(['podetails.*', 'enquiries.*', 'requisitions.*', 'requisition_items.*', 'items_master.*', 'users.name AS bidder_name', 'users.email AS bidder_email'])
        // ->join('enquiries', 'enquiries.id', 'podetails.enquiry_id')
        // ->join('requisitions', 'requisitions.id', 'enquiries.requisition_id')
        // ->join('requisition_items', 'requisition_items.requisition_id', 'requisitions.id')
        // ->join('items_master', 'items_master.id', 'requisition_items.item_master_id')
        // ->join('users', 'users.id', 'enquiries.user_id')
        // ->where('po_reference_no', $poref)
        // ->first();

        $data = PurchaseOrder::with('enquiry', 'enquiry.requisition', 'enquiry.requisition.requisition_items')
        ->where('po_reference_no', $poref)
        ->first();
        // dd($data);
        $pdf = \PDF::loadView('purchase_order.po', ['data' => $data]);
        return $pdf->download('purchase_order_'.$poref.'.pdf');
        // return view('purchase_order.po')->with('data', $data);
    }
}
