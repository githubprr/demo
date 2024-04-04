<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\VendorItem;
use App\VendorItemAttributes;
use App\ItemMaster;
use App\CategoryGroupMaster;
use App\CategoryMaster;
use App\SubcategoryMaster;
use App\FormMaster;
// use App\VendorMaster;
// use App\CompanyMaster;
use App\User;
use App\UomMaster;
use Auth;
use File;

class VendorItemController extends Controller
{
    public function index(){
        if(!$this->getPermission('vendor_items','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $items = VendorItem::with('item_master')->where('user_id',Auth::user()->id)->orderBy('id','desc')->get();
    	return view('vendor_item.list', compact('items'));
    }
    public function add(){
        if(!$this->getPermission('vendor_items','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if(Auth::user()->privileges==3) {
            $items = VendorItem::where('user_id',Auth::user()->id)->orderBy('id','desc')->count();
            if($items==0 || $items>0) {
                $allowed_items_count = 0;
                $subscription_value = $this->getSubscriptionDetails('product_nos');
                if($subscription_value!="-1") {
                    $allowed_items_count = $subscription_value;
                    $special_addon_value = $this->getSpecialAddonDetails(1);
                    if(isset($special_addon_value))
                        $allowed_items_count += $special_addon_value;
                    if($subscription_value==null)
                        return redirect('/vendorItems')->with('error','You dont have any subscription to add product');
                    else if($items>=$allowed_items_count)
                        return redirect('/vendorItems')->with('error','Your subscription allowed you to add only '.$allowed_items_count.' product');
                }
            }
        }
        // if(Auth::user()->privileges==3) {
        //     $my_items_count = 0;
        //     $items = ItemMaster::where('added_by',Auth::user()->id)->pluck('id');
        //     if($items->count()==0 || $items->count()>0) {
        //         $my_items_count = $items->count();
        //         $vendor_items = VendorItem::where('user_id',Auth::user()->id)->whereNotIn('item_master_id',$items)->count();
        //         $my_items_count += $vendor_items;
        //         $subscription_value = $this->getSubscriptionDetails('product_nos');
        //         if($subscription_value!="-1") {
        //             if($subscription_value==null)
        //                 return redirect('/vendorItems')->with('error','You dont have any subscription to add product');
        //             else if($my_items_count>=$subscription_value)
        //                 return redirect('/vendorItems')->with('error','Your subscription allowed you to add only '.$subscription_value.' product');
        //         }
        //     }
        // }
        // $category_groups = CategoryGroupMaster::get();
        $forms = FormMaster::get();
        // $vendors = User::where('privileges',3)->get();
        // $companies = User::where('privileges',2)->get();
        // $item_masters = ItemMaster::where('user_id',Auth::user()->company_id)->orWhere('user_id',1)->get();
        // $item_masters = ItemMaster::where('user_id',Auth::user()->company_id)->orWhere('companies_id',Auth::user()->company_id)->get();
        $uoms = UomMaster::get();
        $companies = User::where('privileges',2)->where('id','<>',2)->get();
        return view('vendor_item.add', compact('uoms','forms','companies'));
    }
    public function save(Request $request){
        if(!$this->getPermission('vendor_items','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $item = new VendorItem;
        $item->item_master_id = $request->item_master_id;
        $item->discount = $request->discount;
        if(isset($request->gst)) {
            $item->gst = $request->gst;
        }
        $item->user_id = Auth::user()->id;
        
        if($item->save()) {
            $vendorItemAttributes = new VendorItemAttributes;
            $vendorItemAttributes->vendor_item_id = $item->id;
            $vendorItemAttributes->price = $request->price;
            $vendorItemAttributes->form_id = $request->form_id;
            $vendorItemAttributes->wt_size = $request->wt_size;
            $vendorItemAttributes->uom_id = $request->uom_id;
            $vendorItemAttributes->save();
            return redirect('/vendorItems')->with('success','Item added successful');
        }
    	else
           return redirect('/vendorItems')->with('error','Item add failed');
    }
    public function edit($id){
        if(!$this->getPermission('vendor_items','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$item = VendorItem::with('vendor_item_attributes')->Where('id',$id)->first();
        if($item && $item->user_id==Auth::user()->id) {
            $item_masters = ItemMaster::where('companies_id',$item->item_master->companies_id)->get();
            // $item_masters = ItemMaster::where('user_id',Auth::user()->company_id)->orWhere('companies_id',Auth::user()->company_id)->get();
            // $category_groups = CategoryGroupMaster::get();
            // $categories = CategoryMaster::where('item_category_group_id', $item->item_category_group_id)->get();
            // $subcategories = SubcategoryMaster::where('item_categories_id', $item->item_categories_id)->get();
            $forms = FormMaster::get();
            // $vendors = User::where('privileges',3)->get();
            // $companies = User::where('privileges',2)->get();
            $uoms = UomMaster::get();
            $companies = User::where('privileges',2)->where('id','<>',2)->get();
            return view('vendor_item.edit',compact('item_masters', 'item', 'forms', 'uoms', 'companies'));
        }
        return redirect('/vendorItems')->with('error','Item not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('vendor_items','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$item = VendorItem::Where('id',$request->id)->first();
        if($item && $item->user_id==Auth::user()->id) {
            $item->item_master_id = $request->item_master_id;
        	// $item->price = $request->price;
            $item->discount = $request->discount;
            // $item->uom_id = $request->uom_id;
            if(isset($request->gst)) {
                $item->gst = $request->gst;
            }
            // $item->form_id = $request->form_id;
            // $item->wt_size = $request->wt_size;
        	if($item->save())
               return redirect('/vendorItems')->with('success','Item updated successful');
        }
        return redirect('/vendorItems')->with('error','Item update failed');
    }
    public function delete($id){
        if(!$this->getPermission('vendor_items','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$item = VendorItem::Where('id',$id)->first();
        if($item) {
            if($item->delete())
               return redirect('/vendorItems')->with('success','Item deleted successful');
        	else
               return redirect('/vendorItems')->with('error','Item delete failed');
        }
        return redirect('/vendorItems')->with('error','Item not found');
    }
}
