<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Shipment;
use App\SalesOrder;
use Auth;
use File;
use Validator;
use PDF;

class ShipmentController extends Controller
{
    public function index(){
        if(!$this->getPermission('shipment','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $shipments = Shipment::with('sales_order')->get();
    	return view('shipment.list', compact('shipments'));
    }
    public function add(){
        if(!$this->getPermission('shipment','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $sales_orders = SalesOrder::get();
        return view('shipment.add', compact('sales_orders'));
    }
    // public function save(Request $request){
    //     $country = new Shipment;
    //     $country->name = $request->name;
    //     if($country->save())
    //        return redirect('/countryMaster')->with('success','Country added successful');
    // 	else
    //        return redirect('/countryMaster')->with('error','Country add failed');
    // }
    public function save(Request $request){
        if(!$this->getPermission('shipment','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        // $input = $request->all();
        // $validator = Validator::make($input, [
        //     'name' => 'required|alpha'
        // ]);
        // if($validator->fails()){
        //     return back()->withError($validator->errors())->withInput();       
        // }
        // dd(1);
        $shipment = new Shipment;
        $shipment->date = $request->date;
        $shipment->sales_order_id = $request->sales_order_id;
        $shipment->vendor_name = $request->vendor_name;
        $shipment->status = $request->status;
        $shipment->user_id = Auth::user()->id;
        if($shipment->save()) {
            $shipment->shipment_no = 'SN'.$shipment->id;
            $shipment->save();
            return redirect('/shipments')->with('success','Shipment added successful');
        }
        else
            return redirect('/shipments')->with('error','Shipment add failed');
    }
    public function edit($id){
        if(!$this->getPermission('shipment','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $shipment = Shipment::Where('id',$id)->first();
    	$sales_orders = SalesOrder::get();
        return view('shipment.edit',compact('shipment','sales_orders'));
    }
    public function update(Request $request){
        if(!$this->getPermission('shipment','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$shipment = Shipment::Where('id',$request->id)->first();
    	$shipment->date = $request->date;
        $shipment->sales_order_id = $request->sales_order_id;
        $shipment->customer_name = $request->customer_name;
        $shipment->gst_no = $request->gst_no;
        $shipment->billing_address = $request->billing_address;
        $shipment->shipping_address = $request->shipping_address;
        $shipment->value = $request->value;
        $shipment->terms_conditions = $request->terms_conditions;
    	if($shipment->save())
           return redirect('/salesOrders')->with('success','Sales Order updated successful');
    	else
           return redirect('/salesOrders')->with('error','Sales Order update failed');
    }
    public function delete($id){
    	if(!$this->getPermission('shipment','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$country = Shipment::Where('id',$id)->first();
    	if($country->delete())
           return redirect('/salesOrders')->with('success','Sales Order deleted successful');
    	else
           return redirect('/salesOrders')->with('error','Sales Order delete failed');
    }
    public function download($id)
    {
        $shref = $id;
        // $data = Shipment::select(['podetails.*', 'enquiries.*', 'requisitions.*', 'requisition_items.*', 'items_master.*', 'users.name AS bidder_name', 'users.email AS bidder_email'])
        // ->join('enquiries', 'enquiries.id', 'podetails.enquiry_id')
        // ->join('requisitions', 'requisitions.id', 'enquiries.requisition_id')
        // ->join('requisition_items', 'requisition_items.requisition_id', 'requisitions.id')
        // ->join('items_master', 'items_master.id', 'requisition_items.item_master_id')
        // ->join('users', 'users.id', 'enquiries.user_id')
        // ->where('po_reference_no', $poref)
        // ->first();

        $data = Shipment::with('sales_order', 'sales_order.purchase_order', 'sales_order.purchase_order.enquiry', 'sales_order.purchase_order.enquiry.requisition', 'sales_order.purchase_order.enquiry.requisition.requisition_items')
        ->where('shipment_no', $shref)
        ->first();
        // dd($data);
        $pdf = \PDF::loadView('shipment.shipment', ['data' => $data]);
        return $pdf->download('shipment_'.$shref.'.pdf');
        // return view('shipment.shipment')->with('data', $data);
    }
}
