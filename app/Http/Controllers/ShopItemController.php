<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ShopItem;
use App\ShopItemReview;
use App\CategoryMaster;
use App\SubcategoryMaster;
use App\FormMaster;
// use App\VendorMaster;
// use App\CompanyMaster;
use App\User;
use App\Cart;
use App\UomMaster;
use Auth;
use File;
use DB;

class ShopItemController extends Controller
{
    public function index(){
        if(!$this->getPermission('shop_items','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $items = ShopItem::with('category')->with('subcategory')->orderBy('id','desc')->get();
    	return view('shop_item.list', compact('items'));
    }
    public function add(){
        if(!$this->getPermission('shop_items','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $categories = CategoryMaster::get();
        $forms = FormMaster::get();
        $uoms = UomMaster::get();
        return view('shop_item.add', compact('categories', 'forms', 'uoms'));
    }
    public function save(Request $request){
        if(!$this->getPermission('shop_items','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if($request->hasFile('image')){
            $file= $_FILES['image']['name'];
            $var=explode(".",$file);
            $ext='.'.end($var);
            $user_id = Auth::user()->id;
            $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
            $filename =  $value.'-'.$user_id .$ext;
            $filepath = public_path('uploads/shop_items/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);

            $item = new ShopItem;
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
            $item->price = $request->price;
            $item->discount = (isset($request->discount))?$request->discount:0;
            $item->hsn_code = $request->hsn_code;
            $item->item_categories_id = $request->item_categories_id;
            $item->item_sub_categories_id = $request->item_sub_categories_id;
            $item->uom_id = $request->uom_id;
            $item->gst = (isset($request->gst))?$request->gst:0;
            $item->form_id = $request->form_id;
            $item->wt_size = $request->wt_size;
            $item->is_featured = $request->is_featured;
            $item->grains = (isset($request->grains))?$request->grains:null;

            if($item->save())
               return redirect('/shopItem')->with('success','Item added successful');
        	else
               return redirect('/shopItem')->with('error','Item add failed');
        }
    }
    public function edit($id){
        if(!$this->getPermission('shop_items','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$item = ShopItem::Where('id',$id)->first();
        if($item) {
            $categories = CategoryMaster::get();
            $subcategories = SubcategoryMaster::where('item_categories_id', $item->item_categories_id)->get();
            $forms = FormMaster::get();
            $uoms = UomMaster::get();
            return view('shop_item.edit',compact('item', 'categories', 'subcategories', 'forms', 'uoms'));
        }
        return redirect('/shopItem')->with('error','Item not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('shop_items','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$item = ShopItem::Where('id',$request->id)->first();
    	if($item) {
            if($request->hasFile('image')){
                $image_path = public_path() . '/uploads/shop_items/'.$item->image;
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
            $item->price = $request->price;
            $item->discount = (isset($request->discount))?$request->discount:0;
            $item->hsn_code = $request->hsn_code;
            $item->item_categories_id = $request->item_categories_id;
            $item->item_sub_categories_id = $request->item_sub_categories_id;
            $item->uom_id = $request->uom_id;
            $item->gst = (isset($request->gst))?$request->gst:0;
            $item->form_id = $request->form_id;
            $item->wt_size = $request->wt_size;
            $item->is_featured = $request->is_featured;
            $item->grains = (isset($request->grains))?$request->grains:0;
        	if($item->save())
               return redirect('/shopItem')->with('success','Item updated successful');
        	else
               return redirect('/shopItem')->with('error','Item update failed');
        }
        return redirect('/shopItem')->with('error','Item not found');
    }
    public function delete($id){
    	if(!$this->getPermission('shop_items','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$item = ShopItem::Where('id',$id)->first();
        if($item) {
            $image_path = public_path() . '/uploads/shop_items/'.$item->item_image;
            if(File::exists($image_path)) {
                File::delete($image_path);
            }
           $cart_item = Cart::Where('item_id',$id)->delete();
        	if($item->delete())
               return redirect('/shopItem')->with('success','Item deleted successful');
        	else
               return redirect('/shopItem')->with('error','Item delete failed');
        }
        return redirect('/shopItem')->with('error','Item not found');
    }
    public function getAllShopItems(){
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
        $shop_items = ShopItem::whereNotNull($column)
            ->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','gst','created_at','updated_at','form_id',$brand_name_lang,'wt_size','discount','image','uom_id',DB::raw('(SELECT avg(rating) FROM shop_item_reviews WHERE shop_item_reviews.shop_item_id=shop_items.id) AS avg_rating'),'is_featured','grains');
        if(request()->has('sort_by')) {
            $sort_by = request()->sort_by;
            if($sort_by=='name_asc')
                $shop_items = $shop_items->orderBy('name', 'asc');
            else if($sort_by=='name_desc')
                $shop_items = $shop_items->orderBy('name', 'desc');
            else if($sort_by=='price_asc')
                $shop_items = $shop_items->orderBy('price', 'asc');
            else if($sort_by=='price_desc')
                $shop_items = $shop_items->orderBy('price', 'desc');
        }
        else
            $shop_items = $shop_items->orderBy('name', 'asc');
            
        $shop_items = $shop_items->get();
        if(count($shop_items)) {
            $shop_items = $shop_items->map(function($value, $key) {
                if($value->avg_rating==null)
                    $value->avg_rating = 0;
                else
                    $value->avg_rating = round($value->avg_rating,2);
                return $value;
            });
            return response()->json([
                'errorCode' => 0,
                'data' => $shop_items,
                'message' => 'Get shop items successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get shop items failed'
        ]);
    }
    public function apiSaveReview(Request $request){
        $user = auth()->guard('api')->user();
        $shop_item_review_exists = ShopItemReview::where('shop_item_id',$request->shop_item_id)->where('user_id',$user->id)->exists();
        if(!$shop_item_review_exists) {
            $shop_item_review = new ShopItemReview;
            $shop_item_review->shop_item_id = $request->shop_item_id;
            $shop_item_review->user_id = $user->id;
            $shop_item_review->rating = $request->rating;
            if(isset($request->review))
                $shop_item_review->review = urldecode($request->review);
            $shop_item_review->save();
            if($shop_item_review) {
                return response()->json([
                    'errorCode' => 0,
                    'message' => 'Shop item review added successful'
                ]);
            }

            return response()->json([
                'errorCode' => 1,
                'message' => 'Shop item review add failed'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'You have already given shop item review'
        ]);
    }
    public function apiGetShopItemDetails($id){
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
        $shop_item = ShopItem::whereNotNull($column)->with('shop_item_reviews.user')->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','gst','created_at','updated_at','form_id',$brand_name_lang,'wt_size','discount','image','uom_id','is_featured','grains')->where('id', $id)->first();
        if($shop_item) {
            $rating_array = array();
            for ($i=0; $i < 5; $i++) { 
                $new_rating_group['rating'] = $i+1;
                $new_rating_group['total'] = 0;
                array_push($rating_array, $new_rating_group);
            }
            if(count($shop_item->shop_item_reviews)) {
                $rating_group = ShopItemReview::where('shop_item_id',$id)->select('rating', DB::raw('count(rating) as total'))->groupBy('rating')->orderBy('rating')->get();
                if(count($rating_group)) {
                    foreach ($rating_group as $key => $value) {
                        $rating_array[$value->rating-1]['total'] = $value->total;
                    }
                }
            }
            // else
            //     $shop_item->rating_group = 0;
            $shop_item->rating_group = $rating_array;

            $shop_item->my_rating = ShopItemReview::where('shop_item_id',$id)->where('user_id',$user->id)->select('rating','review')->first();
            return response()->json([
                'errorCode' => 0,
                'data' => $shop_item,
                'message' => 'Get shop item details successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get shop item details failed'
        ]);
    }
    public function apiGetFeaturedShopItems(){
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
        $shop_items = ShopItem::whereNotNull($column)
            ->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','gst','created_at','updated_at','form_id',$brand_name_lang,'wt_size','discount','image','uom_id',DB::raw('(SELECT avg(rating) FROM shop_item_reviews WHERE shop_item_reviews.shop_item_id=shop_items.id) AS avg_rating'),'is_featured','grains')
            ->where('is_featured', 1)
            ->inRandomOrder()
            ->take(3)
            ->get();
        if($shop_items) {
            return response()->json([
                'errorCode' => 0,
                'data' => $shop_items,
                'message' => 'Get featured shop items successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get featured shop items failed'
        ]);
    }
}
