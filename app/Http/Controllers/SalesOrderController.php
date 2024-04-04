<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\SalesOrder;
use App\PurchaseOrder;
use Auth;
use File;
use Validator;
use PDF;

class SalesOrderController extends Controller
{
    public function index(){
        if(!$this->getPermission('sales_order','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $sales_orders = SalesOrder::with('purchase_order')->withCount('shipments')->get();
    	return view('sales_order.list', compact('sales_orders'));
    }
    public function add(){
        if(!$this->getPermission('sales_order','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $purchase_orders = PurchaseOrder::get();
        return view('sales_order.add', compact('purchase_orders'));
    }
    // public function save(Request $request){
    //     $country = new SalesOrder;
    //     $country->name = $request->name;
    //     if($country->save())
    //        return redirect('/countryMaster')->with('success','Country added successful');
    // 	else
    //        return redirect('/countryMaster')->with('error','Country add failed');
    // }
    public function save(Request $request){
        if(!$this->getPermission('sales_order','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        // $input = $request->all();
        // $validator = Validator::make($input, [
        //     'name' => 'required|alpha'
        // ]);
        // if($validator->fails()){
        //     return back()->withError($validator->errors())->withInput();       
        // }
        // dd(1);
        $sales_order = new SalesOrder;
        $sales_order->date = $request->date;
        $sales_order->purchase_order_id = $request->purchase_order_id;
        $sales_order->customer_name = $request->customer_name;
        $sales_order->gst_no = $request->gst_no;
        $sales_order->billing_address = $request->billing_address;
        $sales_order->shipping_address = $request->shipping_address;
        $sales_order->value = $request->value;
        $sales_order->terms_conditions = $request->terms_conditions;
        $sales_order->user_id = Auth::user()->id;
        if($sales_order->save()) {
            $sales_order->so_no = 'SO'.$sales_order->id;
            $sales_order->save();
            return redirect('/salesOrders')->with('success','Sales Order added successful');
        }
        else
            return redirect('/salesOrders')->with('error','Sales Order add failed');
    }
    public function edit($id){
        if(!$this->getPermission('sales_order','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $sales_order = SalesOrder::Where('id',$id)->first();
    	$purchase_orders = PurchaseOrder::get();
        return view('sales_order.edit',compact('sales_order','purchase_orders'));
    }
    public function update(Request $request){
        if(!$this->getPermission('sales_order','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$sales_order = SalesOrder::Where('id',$request->id)->first();
    	$sales_order->date = $request->date;
        $sales_order->purchase_order_id = $request->purchase_order_id;
        $sales_order->customer_name = $request->customer_name;
        $sales_order->gst_no = $request->gst_no;
        $sales_order->billing_address = $request->billing_address;
        $sales_order->shipping_address = $request->shipping_address;
        $sales_order->value = $request->value;
        $sales_order->terms_conditions = $request->terms_conditions;
    	if($sales_order->save())
           return redirect('/salesOrders')->with('success','Sales Order updated successful');
    	else
           return redirect('/salesOrders')->with('error','Sales Order update failed');
    }
    public function delete($id){
        if(!$this->getPermission('sales_order','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$country = SalesOrder::Where('id',$id)->first();
    	if($country->delete())
           return redirect('/salesOrders')->with('success','Sales Order deleted successful');
    	else
           return redirect('/salesOrders')->with('error','Sales Order delete failed');
    }
    public function download($id)
    {
        $soref = $id;
        // $data = SalesOrder::select(['podetails.*', 'enquiries.*', 'requisitions.*', 'requisition_items.*', 'items_master.*', 'users.name AS bidder_name', 'users.email AS bidder_email'])
        // ->join('enquiries', 'enquiries.id', 'podetails.enquiry_id')
        // ->join('requisitions', 'requisitions.id', 'enquiries.requisition_id')
        // ->join('requisition_items', 'requisition_items.requisition_id', 'requisitions.id')
        // ->join('items_master', 'items_master.id', 'requisition_items.item_master_id')
        // ->join('users', 'users.id', 'enquiries.user_id')
        // ->where('po_reference_no', $poref)
        // ->first();

        $data = SalesOrder::with('purchase_order', 'purchase_order.enquiry', 'purchase_order.enquiry.requisition', 'purchase_order.enquiry.requisition.requisition_items')
        ->where('so_no', $soref)
        ->first();
        // dd($data);
        $pdf = \PDF::loadView('sales_order.sales_order', ['data' => $data]);
        return $pdf->download('sales_order_'.$soref.'.pdf');
        // return view('sales_order.sales_order')->with('data', $data);
    }
    public function getDetailsById($id){
    	$data = SalesOrder::with('purchase_order', 'purchase_order.enquiry', 'purchase_order.enquiry.requisition', 'purchase_order.enquiry.requisition.requisition_items', 'purchase_order.enquiry.requisition.requisition_items.item')
        ->where('id', $id)
        ->first();

        $requisition_no = $data->purchase_order->enquiry->requisition_id;
        $details = array();
        $sr = 0;
        foreach ($data->purchase_order->enquiry->requisition->requisition_items as $key => $requisition_item) {
            $details[$sr]['requisition_no'] = $data->purchase_order->enquiry->requisition->reference_requisition_no;
            $details[$sr]['item_name'] = $requisition_item->item->name;
            $details[$sr]['item_description'] = $requisition_item->item->description;
            $details[$sr]['item_price'] = $requisition_item->item->price;
            $details[$sr]['qty'] = $requisition_item->qty;
            $details[$sr]['total'] = $requisition_item->qty*$requisition_item->item->price;
            $sr++;
        }
        return response()->json($details);
    }
}
