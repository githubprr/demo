<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ItemMaster;
use App\VendorItem;
use App\CategoryGroupMaster;
use App\CategoryMaster;
use App\SubcategoryMaster;
use App\FormMaster;
// use App\VendorMaster;
// use App\CompanyMaster;
use App\User;
use App\UomMaster;
use App\ItemMasterReview;
use Auth;
use File;
use DB;

class ItemMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('items','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if(Auth::user()->privileges==2)
            $items = ItemMaster::with('category')->with('subcategory')->where('companies_id',Auth::user()->id)->orWhere('added_by',Auth::user()->id)->orderBy('id','desc')->get();
        else if(Auth::user()->privileges==3)
            $items = ItemMaster::with('category')->with('subcategory')->where('added_by',Auth::user()->id)->orderBy('id','desc')->get();
        else
            $items = ItemMaster::with('category')->with('subcategory')->orderBy('id','desc')->get();
    	return view('item_master.list', compact('items'));
    }
    public function add(){
        if(!$this->getPermission('items','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        // if(Auth::user()->privileges==3) {
        //     $items = ItemMaster::with('category')->with('subcategory')->where('vendors_id',Auth::user()->id)->orderBy('id','desc')->get();
        //     if(sizeof($items)==0 || sizeof($items)>0) {
        //         $subscription_value = $this->getSubscriptionDetails('product_nos');
        //         if($subscription_value!="-1") {
        //             if($subscription_value==null)
        //                 return redirect('/itemMaster')->with('error','You dont have any subscription to add product');
        //             else if(sizeof($items)==$subscription_value)
        //                 return redirect('/itemMaster')->with('error','Your subscription allowed you to add only '.$subscription_value.' product');
        //         }
        //     }
        // }
        if(Auth::user()->privileges==3) {
            $my_items_count = 0;
            $items = ItemMaster::where('added_by',Auth::user()->id)->pluck('id');
            if($items->count()==0 || $items->count()>0) {
                $my_items_count = $items->count();
                $vendor_items = VendorItem::where('user_id',Auth::user()->id)->whereNotIn('item_master_id',$items)->count();
                $my_items_count += $vendor_items;
                $subscription_value = $this->getSubscriptionDetails('product_nos');
                if($subscription_value!="-1") {
                    $allowed_items_count = $subscription_value;
                    $allowed_items_count = $subscription_value;
                    $special_addon_value = $this->getSpecialAddonDetails(1);
                    if(isset($special_addon_value))
                        $allowed_items_count += $special_addon_value;
                    if($subscription_value==null)
                        return redirect('/itemMaster')->with('error','You dont have any subscription to add product');
                    else if($my_items_count>=$allowed_items_count)
                        return redirect('/itemMaster')->with('error','Your subscription allowed you to add only '.$allowed_items_count.' product');
                }
            }
        }
        $category_groups = CategoryGroupMaster::get();
        // $forms = FormMaster::get();
        // $vendors = User::where('privileges',3)->get();
        $companies = User::where('privileges',2)->where('id','<>',2)->get();
        $companies = User::where('privileges',2)->where('id','<>',2)->get();
        // $uoms = UomMaster::get();
        return view('item_master.add', compact('category_groups', 'companies'));
    }
    public function save(Request $request){
        if(!$this->getPermission('items','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if($request->hasFile('image')){
            $file= $_FILES['image']['name'];
            $var=explode(".",$file);
            $ext='.'.end($var);
            $user_id = Auth::user()->id;
            $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
            $filename =  $value.'-'.$user_id .$ext;
            $filepath = public_path('uploads/items/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);

            $item = new ItemMaster;
            $item->name = $request->name;
            $item->name_hn = $request->name_hn;
        	$item->name_mr = $request->name_mr;
            $item->image = $filename;
            $item->brand_name = $request->brand_name;
            $item->brand_name_hn = $request->brand_name_hn;
            $item->brand_name_mr = $request->brand_name_mr;
            $item->description = $request->description;
            $item->description_hn = $request->description_hn;
            $item->description_mr = $request->description_mr;
            $item->hsn_code = $request->hsn_code;
            $item->item_category_group_id = $request->item_category_group_id;
            $item->item_categories_id = $request->item_categories_id;
            $item->item_sub_categories_id = (isset($request->item_sub_categories_id))?$request->item_sub_categories_id:null;
            if(Auth::user()->privileges==3)
                $item->companies_id = 3;
            else if(Auth::user()->privileges==2)
                $item->companies_id = Auth::user()->id;
            else
                $item->companies_id = $request->companies_id;
            // if(Auth::user()->privileges==1)
                // $item->approved = 0;
            // else
                // $item->approved = 1;
            $item->added_by = Auth::user()->id;
            if(Auth::user()->privileges==3)
                $item->is_vendor_item = 1;
            else
                $item->is_vendor_item = 0;

            if($item->save())
               return redirect('/itemMaster')->with('success','Item added successful');
        	else
               return redirect('/itemMaster')->with('error','Item add failed');
        }
    }
    public function edit($id){
        if(!$this->getPermission('items','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$item = ItemMaster::Where('id',$id)->first();
        if($item && (Auth::user()->privileges==1 || $item->added_by==Auth::user()->id)) {
            $category_groups = CategoryGroupMaster::get();
            $categories = CategoryMaster::where('item_category_group_id', $item->item_category_group_id)->get();
            $subcategories = SubcategoryMaster::where('item_categories_id', $item->item_categories_id)->get();
            // $forms = FormMaster::get();
            // $vendors = User::where('privileges',3)->get();
            $companies = User::where('privileges',2)->where('id','<>',2)->get();
            // $uoms = UomMaster::get();
            return view('item_master.edit',compact('item', 'category_groups', 'categories', 'subcategories', 'companies'));
        }
        return redirect('/itemMaster')->with('error','Item not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('items','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$item = ItemMaster::Where('id',$request->id)->first();
        if($item) {
        	if($request->hasFile('image')){
                $image_path = public_path() . '/uploads/items/'.$item->image;
                if(File::exists($image_path)) {
                    File::delete($image_path);
                }
                $file= $_FILES['image']['name'];
                $var=explode(".",$file);
                $ext='.'.end($var);
                $user_id = Auth::user()->id;
                $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename =  $value.'-'.$user_id .$ext;
                $filepath = public_path('uploads/items/');

                if(!File::isDirectory($filepath))
                    File::makeDirectory($filepath, 0777, true, true);

                move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);
                $item->image = $filename;
            }
            $item->name = $request->name;
            $item->name_hn = $request->name_hn;
            $item->name_mr = $request->name_mr;
            // $item->image = $filename;
            $item->brand_name = $request->brand_name;
            $item->brand_name_hn = $request->brand_name_hn;
            $item->brand_name_mr = $request->brand_name_mr;
            $item->description = $request->description;
            $item->description_hn = $request->description_hn;
            $item->description_mr = $request->description_mr;
            $item->hsn_code = $request->hsn_code;
            $item->item_category_group_id = $request->item_category_group_id;
            $item->item_categories_id = $request->item_categories_id;
            $item->item_sub_categories_id = (isset($request->item_sub_categories_id))?$request->item_sub_categories_id:null;
            if(Auth::user()->privileges==3)
                $item->companies_id = 3;
            else if(Auth::user()->privileges==2)
                $item->companies_id = Auth::user()->id;
            else
                $item->companies_id = $request->companies_id;
            if($item->save())
               return redirect('/itemMaster')->with('success','Item updated successful');
        	else
               return redirect('/itemMaster')->with('error','Item update failed');
        }
        return redirect('/itemMaster')->with('error','Item not found');
    }
    public function delete($id){
        if(!$this->getPermission('items','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$item = ItemMaster::Where('id',$id)->first();
        if($item) {
            $image_path = public_path() . '/uploads/items/'.$item->item_image;
            if(File::exists($image_path)) {
                File::delete($image_path);
            }
        	if($item->delete())
               return redirect('/itemMaster')->with('success','Item deleted successful');
        	else
               return redirect('/itemMaster')->with('error','Item delete failed');
        }
        return redirect('/itemMaster')->with('error','Item not found');
    }
    // public function unapprovedItemList(){
    //     $items = ItemMaster::with('category')->with('subcategory')->where('vendors_id',Auth::user()->id)->where('approved',0)->orderBy('id','desc')->get();
    //     return view('item_master.unapproved_list', compact('items'));
    // }
    // public function approveItem($id){
    //     $item = ItemMaster::where('id',$id)->first();
    //     $item->approved = 1;
    //     if($item->save())
    //         return redirect('/unapprovedItemList')->with('success','Item approved successful');
    //     else
    //        return redirect('/unapprovedItemList')->with('error','Item approve failed');
    // }
    public function getItemsByCompany($id){
        // $items = ItemMaster::Where('companies_id',$id)->get();
        $items = ItemMaster::Where('companies_id',$id)->Where('item_category_group_id',Auth::user()->category_group_id)->Where('is_vendor_item',0)->get();
        $vendor_items = ItemMaster::Where('companies_id',$id)->Where('item_category_group_id',Auth::user()->category_group_id)->Where('added_by',Auth::user()->id)->get();
        $merged = $items->merge($vendor_items);
        return response()->json($merged);
    }
    public function apiSaveReview(Request $request){
        $user = auth()->guard('api')->user();
        $item_review_exists = ItemMasterReview::where('item_master_id',$request->item_master_id)->where('user_id',$user->id)->exists();
        if(!$item_review_exists) {
            $item_master_review = new ItemMasterReview;
            $item_master_review->item_master_id = $request->item_master_id;
            $item_master_review->user_id = $user->id;
            $item_master_review->rating = $request->rating;
            if(isset($request->review))
                $item_master_review->review = urldecode($request->review);
            $item_master_review->save();
            if($item_master_review) {
                return response()->json([
                    'errorCode' => 0,
                    'message' => 'Item review added successful'
                ]);
            }

            return response()->json([
                'errorCode' => 1,
                'message' => 'Item review add failed'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'You have already given item review'
        ]);
    }
    public function apiGetItemDetails($id){
        $user = auth()->guard('api')->user();
        $header = request()->header('lang');
        $lang = 'name';
        $brand_name_lang = 'brand_name';
        $description_lang = 'description';
        $column = 'name';
        if($header=='hn') {
            $lang = 'name_hn as name';
            $brand_name_lang = 'brand_name_hn as brand_name';
            $description_lang = 'description_hn as description';
            $column = 'name_hn';
        }
        else if($header=='mr') {
            $lang = 'name_mr as name';
            $brand_name_lang = 'brand_name_mr as brand_name';
            $description_lang = 'description_mr as description';
            $column = 'name_mr';
        }
        $item_master = ItemMaster::whereNotNull($column)->with('item_master_reviews.user')->select('id',$lang,$description_lang,'hsn_code','item_category_group_id','item_categories_id','item_sub_categories_id','created_at','updated_at',$brand_name_lang,'image')->where('id', $id)->first();
        if($item_master) {
            $rating_array = array();
            for ($i=0; $i < 5; $i++) { 
                $new_rating_group['rating'] = $i+1;
                $new_rating_group['total'] = 0;
                array_push($rating_array, $new_rating_group);
            }
            if(count($item_master->item_master_reviews)) {
                $rating_group = ItemMasterReview::where('item_master_id',$id)->select('rating', DB::raw('count(rating) as total'))->groupBy('rating')->orderBy('rating')->get();
                if(count($rating_group)) {
                    foreach ($rating_group as $key => $value) {
                        $rating_array[$value->rating-1]['total'] = $value->total;
                    }
                }
            }
            // else
            //     $item_master->rating_group = 0;
            $item_master->rating_group = $rating_array;

            $item_master->my_rating = ItemMasterReview::where('item_master_id',$id)->where('user_id',$user->id)->select('rating','review')->first();
            return response()->json([
                'errorCode' => 0,
                'data' => $item_master,
                'message' => 'Get item details successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get item details failed'
        ]);
    }
}
