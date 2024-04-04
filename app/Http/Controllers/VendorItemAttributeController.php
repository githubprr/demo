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

class VendorItemAttributeController extends Controller
{
    public function saveUpdate(Request $request){
        if(!$this->getPermission('vendor_items','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if(!$this->getPermission('vendor_items','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $id = 0;
        $msg = "add";
        if(isset($request->vendor_item_attribute_id)) {
            $id = $request->vendor_item_attribute_id;
            $msg = "update";
        }
        if($id==0) {
            $vendorItemAttributes = new VendorItemAttributes;
            $vendorItemAttributes->vendor_item_id = $request->vendor_item_id;
        }
        else
            $vendorItemAttributes = VendorItemAttributes::where('id',$id)->first();
        
        $vendorItemAttributes->price = $request->price;
        $vendorItemAttributes->form_id = $request->form_id;
        $vendorItemAttributes->wt_size = $request->wt_size;
        $vendorItemAttributes->uom_id = $request->uom_id;
        $vendorItemAttributes->stock = $request->stock;
        $vendorItemAttributes->stock_threshold = $request->stock_threshold;
        if($vendorItemAttributes->save())
            return back()->with('success','Item attribute '.$msg.'ed successful');
        else
           return back()->with('error','Item attribute '.$msg.' failed');
    }
    public function delete($id){
        if(!$this->getPermission('vendor_items','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$vendorItemAttribute = VendorItemAttributes::Where('id',$id)->first();
        if($vendorItemAttribute) {
            $vendorItemAttributesCount = VendorItemAttributes::where('vendor_item_id',$vendorItemAttribute->vendor_item_id)->count();
            if($vendorItemAttributesCount==1)
               return back()->with('error','You can not delete this item attribute');

            if($vendorItemAttribute->delete())
               return back()->with('success','Item attribute deleted successful');
        	else
               return back()->with('error','Item attribute delete failed');
        }
        return redirect('/vendorItems')->with('error','Item attribute not found');
    }
}
