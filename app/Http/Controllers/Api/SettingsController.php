<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Privilege;
use App\CountryMaster;
use App\StateMaster;
use App\DistrictMaster;
use App\TalukaMaster;
use App\VillageMaster;
use App\ItemMaster;
use App\ShopItem;
use App\CategoryGroupMaster;
use App\CategoryMaster;
use App\SubcategoryMaster;
use App\CompanyMaster;
use App\User;
use App\ForumQuestion;
use App\ForumAnswer;
use App\VendorItem;
use App\VendorItemAttributes;
use App\CompanyUserArea;
use App\VendorRequisition;
use App\ItemMasterReview;
use DB;
use stdClass;

class SettingsController extends Controller
{
    public function getPrivileges(Request $request)
    {
        $privileges = Privilege::select('id', 'name')->orderBy('name', 'asc')->get();
        if(count($privileges)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $privileges,
                'message' => 'Get privileges successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get privileges failed'
        ]);
    }
	
	public function getCountries(Request $request)
	{
		$header = request()->header('lang');
		$lang = 'name';
		$column = 'name';
		if($header=='hn') {
			$lang = 'name_hn as name';
			$column = 'name_hn';
		}
		else if($header=='mr') {
			$lang = 'name_mr as name';
			$column = 'name_mr';
		}
		$countries = CountryMaster::whereNotNull($column)->select('id', $lang)->orderBy('name', 'asc')->get();
		if(count($countries)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $countries,
                'message' => 'Get countries successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get countries failed'
        ]);
	}

	public function getStates($country_id)
	{
		$header = request()->header('lang');
		$lang = 'name';
		$column = 'name';
		if($header=='hn') {
			$lang = 'name_hn as name';
			$column = 'name_hn';
		}
		else if($header=='mr') {
			$lang = 'name_mr as name';
			$column = 'name_mr';
		}
		$states = StateMaster::whereNotNull($column)->select('id', $lang)->where('country_id', $country_id)->orderBy('name', 'asc')->get();
		if(count($states)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $states,
                'message' => 'Get states successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get states failed'
        ]);
	}

	public function getDistricts($state_id)
	{
		$header = request()->header('lang');
		$lang = 'name';
		$column = 'name';
		if($header=='hn') {
			$lang = 'name_hn as name';
			$column = 'name_hn';
		}
		else if($header=='mr') {
			$lang = 'name_mr as name';
			$column = 'name_mr';
		}
		$districts = DistrictMaster::whereNotNull($column)->select('id', $lang)->where('state_id', $state_id)->orderBy('name', 'asc')->get();
		if(count($districts)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $districts,
                'message' => 'Get districts successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get districts failed'
        ]);
	}

	public function getTalukas($district_id)
	{
		$header = request()->header('lang');
		$lang = 'name';
		$column = 'name';
		if($header=='hn') {
			$lang = 'name_hn as name';
			$column = 'name_hn';
		}
		else if($header=='mr') {
			$lang = 'name_mr as name';
			$column = 'name_mr';
		}
		$talukas = TalukaMaster::whereNotNull($column)->select('id', $lang)->where('district_id', $district_id)->orderBy('name', 'asc')->get();
		if(count($talukas)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $talukas,
                'message' => 'Get talukas successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get talukas failed'
        ]);
	}

	public function getVillages($taluka_id)
	{
		$header = request()->header('lang');
		$lang = 'name';
		$column = 'name';
		if($header=='hn') {
			$lang = 'name_hn as name';
			$column = 'name_hn';
		}
		else if($header=='mr') {
			$lang = 'name_mr as name';
			$column = 'name_mr';
		}
		$villages = VillageMaster::whereNotNull($column)->select('id', $lang)->where('taluka_id', $taluka_id)->orderBy('name', 'asc')->get();
		if(count($villages)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $villages,
                'message' => 'Get villages successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get villages failed'
        ]);
	}
	
	public function getAllItems(Request $request)
	{
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
		// $items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')->orderBy('name', 'asc')->get();
		$items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'hsn_code','item_categories_id','item_sub_categories_id','created_at','updated_at','companies_id',$brand_name_lang,'image')->orderBy('name', 'asc')->get();
		if(count($items)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $items,
                'message' => 'Get items successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get items failed'
        ]);
	}

	// public function getNearbyItems()
	// {
	// 	$user = auth()->guard('api')->user();
	// 	$header = request()->header('lang');
	// 	$lang = 'name';
	// 	$brand_name_lang = 'brand_name';
	// 	$description_lang = 'description';
	// 	$column = 'name';
	// 	if($header=='hn') {
	// 		$lang = 'name_hn as name';
	// 		$brand_name_lang = 'brand_name_hn as brand_name';
	// 		$description_lang = 'description_hn as description';
	// 		$column = 'name_hn';
	// 	}
	// 	else if($header=='mr') {
	// 		$lang = 'name_mr as name';
	// 		$brand_name_lang = 'brand_name_mr as brand_name';
	// 		$description_lang = 'description_mr as description';
	// 		$column = 'name_mr';
	// 	}
	// 	$vendors_ids = User::where('privileges',3)->where('district_id',$user->district_id)->pluck('id');
	// 	$items = ItemMaster::whereNotNull($column)->whereIn('vendors_id',$vendors_ids)->with('vendor')->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')->orderBy('name', 'asc')->get();
	// 	return response()->json($items);
	// }

	public function getNearbyItems()
	{
		if(!request()->has('lat') || !request()->has('lng')) {
			return response()->json([
                'errorCode' => 1,
                'message' => 'Latitude and Longitude is required'
            ]);
		}
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
		// $vendors_ids = User::where('privileges',3)->where('district_id',$user->district_id)->pluck('id');
		$lat = request()->lat;
		$lng = request()->lng;
		$radius = (request()->has('radius'))?request()->radius:5;
		$vendors_ids = User::
			where('privileges',3)
			->select(DB::raw("id, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
			->havingRaw('distance <= '.$radius)
			// ->orderBy('distance')
        	// ->get();
        	->pluck('id');
		// dd($vendors_ids->toArray());
        if(request()->has('sub_category'))
			$items = ItemMaster::whereNotNull($column)->whereIn('vendors_id',$vendors_ids)->with('vendor')->where('item_sub_categories_id',request()->sub_category)->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')->orderBy('name', 'asc')->get();
		else
			$items = ItemMaster::whereNotNull($column)->whereIn('vendors_id',$vendors_ids)->with('vendor')->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')->orderBy('name', 'asc')->get();
		if(count($items)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $items,
                'message' => 'Get items successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get items failed'
        ]);
	}

	public function getCategoryGroups(Request $request)
	{
		$header = request()->header('lang');
		$lang = 'name';
		$column = 'name';
		if($header=='hn') {
			$lang = 'name_hn as name';
			$column = 'name_hn';
		}
		else if($header=='mr') {
			$lang = 'name_mr as name';
			$column = 'name_mr';
		}
		$category_groups = CategoryGroupMaster::whereNotNull($column)->select('id', $lang, 'image')->orderBy('name', 'asc')->get();
		if(count($category_groups)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $category_groups,
                'message' => 'Get category groups successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get category groups failed'
        ]);
	}

	public function getCategories(Request $request)
	{
		$header = request()->header('lang');
		$lang = 'name';
		$column = 'name';
		if($header=='hn') {
			$lang = 'name_hn as name';
			$column = 'name_hn';
		}
		else if($header=='mr') {
			$lang = 'name_mr as name';
			$column = 'name_mr';
		}
		$categories = CategoryMaster::whereNotNull($column)->select('id', $lang, 'image')->orderBy('name', 'asc')->get();
		if(count($categories)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $categories,
                'message' => 'Get categories successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get categories failed'
        ]);
	}

	public function getCategoriesByCategoryGroup($cat_group_id)
	{
		$header = request()->header('lang');
		$lang = 'name';
		$column = 'name';
		if($header=='hn') {
			$lang = 'name_hn as name';
			$column = 'name_hn';
		}
		else if($header=='mr') {
			$lang = 'name_mr as name';
			$column = 'name_mr';
		}
		$categories = CategoryMaster::whereNotNull($column)->where('item_category_group_id', $cat_group_id)->select('id', $lang, 'image')->orderBy('name', 'asc')->get();
		if(count($categories)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $categories,
                'message' => 'Get categories successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get categories failed'
        ]);
	}

	public function getSubCategories(Request $request)
	{
		$header = request()->header('lang');
		$lang = 'name';
		$column = 'name';
		if($header=='hn') {
			$lang = 'name_hn as name';
			$column = 'name_hn';
		}
		else if($header=='mr') {
			$lang = 'name_mr as name';
			$column = 'name_mr';
		}
		$sub_categories = SubcategoryMaster::whereNotNull($column)->select('id', $lang, 'image')->orderBy('name', 'asc')->get();
		if(count($sub_categories)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $sub_categories,
                'message' => 'Get sub categories successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get sub categories failed'
        ]);
	}

	public function getSubCategoriesByCategory($cat_id)
	{
		$header = request()->header('lang');
		$lang = 'name';
		$column = 'name';
		if($header=='hn') {
			$lang = 'name_hn as name';
			$column = 'name_hn';
		}
		else if($header=='mr') {
			$lang = 'name_mr as name';
			$column = 'name_mr';
		}
		$sub_categories = SubcategoryMaster::whereNotNull($column)->where('item_categories_id', $cat_id)->select('id', $lang, 'image')->orderBy('name', 'asc')->get();
		if(count($sub_categories)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $sub_categories,
                'message' => 'Get sub categories successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get sub categories failed'
        ]);
	}

	public function getItemsByCompany($company_id)
	{
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
		// $items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')->where('companies_id',$company_id)->orderBy('name', 'asc')->get();
		$items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'hsn_code','item_categories_id','item_sub_categories_id','created_at','updated_at','companies_id',$brand_name_lang,'image')->where('companies_id',$company_id)->orderBy('name', 'asc')->get();
		if(count($items)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $items,
                'message' => 'Get items successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get items failed'
        ]);
	}

	public function getItemsBySubCategoryold($sub_cat_id)
	{
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
		// $items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')->where('item_sub_categories_id',$sub_cat_id)->orderBy('name', 'asc')->get();
		$items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'hsn_code','item_categories_id','item_sub_categories_id','created_at','updated_at','companies_id',$brand_name_lang,'image')->where('item_sub_categories_id',$sub_cat_id)->orderBy('name', 'asc')->get();
		if(count($items)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $items,
                'message' => 'Get items successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get items failed'
        ]);
	}

	public function getItemsBySubCategory($sub_cat_id)
	{
		if(!request()->has('lat') || !request()->has('lng')) {
			return response()->json([
                'errorCode' => 1,
                'message' => 'Latitude and Longitude is required'
            ]);
		}
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
		$subcategoryMaster = SubcategoryMaster::with('category.category_group')->where('id',$sub_cat_id)->first();
		if(!$subcategoryMaster) {
			return response()->json([
	            'errorCode' => 1,
	            'message' => 'Subcategory id is not valid'
	        ]);
		}
		
		$lat = request()->lat;
		$lng = request()->lng;
		$radius = (isset($subcategoryMaster) && isset($subcategoryMaster->category) && isset($subcategoryMaster->category->category_group) && isset($subcategoryMaster->category->category_group->vendor_requisition_distance))?$subcategoryMaster->category->category_group->vendor_requisition_distance:10;
		$vendors = User::
			where('privileges',3)
			->where('is_subscribed',1)
			->select(DB::raw("id, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
			->havingRaw('distance <= '.$radius)
        	->get('id');
        	// ->pluck('id');
        if(sizeof($vendors)>0) {
			$vendors_ids = array();
			foreach ($vendors as $key => $vendor) {
                $allowed_requisition = true;
                $vendor_requisitions_count = 0;
                $vendorRequisitions = VendorRequisition::where('user_id',$vendor->id)->count();
                if($vendorRequisitions==0 || $vendorRequisitions>0) {
                    $vendor_requisitions_count = $vendorRequisitions;
                    $subscription_value = $this->getSubscriptionDetails('requisition_nos',$vendor->id);
                    if($subscription_value!="-1") {
                        $allowed_items_count = $subscription_value;
                        $special_addon_value = $this->getSpecialAddonDetails(2,$vendor->id);
                        if(isset($special_addon_value))
                            $allowed_items_count += $special_addon_value;
                        if($subscription_value==null)
                            $allowed_requisition = false;
                        else if($vendor_requisitions_count>=$allowed_items_count)
                            $allowed_requisition = false;
                    }
                    if($allowed_requisition)
                    	array_push($vendors_ids, $vendor->id);
                }
            }
            if(count($vendors_ids)==0) {
            	return response()->json([
		            'errorCode' => 1,
		            'message' => 'No items by subcategory found in nearby location'
		        ]);
            }
			$vendor_items = ItemMaster::
				join('vendor_items','vendor_items.item_master_id','items_master.id')
				->whereIn('vendor_items.user_id',$vendors_ids)
				->whereNotNull($column)
				->select('items_master.id',$lang,$description_lang,'hsn_code','item_categories_id','item_sub_categories_id','items_master.created_at','items_master.updated_at','companies_id',$brand_name_lang,'image')->where('item_sub_categories_id',$sub_cat_id)->orderBy('name', 'asc')
				->groupBy('items_master.id')
				->get();
	        if(count($vendor_items)) {
		        return response()->json([
	                'errorCode' => 0,
	                'data' => $vendor_items,
	                'message' => 'Get nearby items by subcategory successful'
	            ]);
	        }
		}
		return response()->json([
            'errorCode' => 1,
            'message' => 'No items by subcategory found in nearby location'
        ]);
	}

	public function getCompanies(Request $request)
	{
		$companies = User::
			select('id','name','email','mobile','photo',DB::raw('(SELECT avg(rating) FROM company_reviews WHERE company_reviews.company_id=users.id) AS avg_rating'))
            ->where('privileges',2)
            ->get();
		if(count($companies)) {
			$companies = $companies->map(function($value, $key) {
                if($value->avg_rating==null)
                    $value->avg_rating = 0;
                else
                    $value->avg_rating = round($value->avg_rating,2);
                return $value;
            });
            return response()->json([
                'errorCode' => 0,
                'data' => $companies,
                'message' => 'Get companies successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get companies failed'
        ]);
	}

	public function getNearbyVendorsold()
	{
		if(!request()->has('lat') || !request()->has('lng')) {
			return response()->json([
                'errorCode' => 1,
                'message' => 'Latitude and Longitude is required'
            ]);
		}
		$user = auth()->guard('api')->user();
		$header = request()->header('lang');
		$lat = request()->lat;
		$lng = request()->lng;
		$radius = (request()->has('radius'))?request()->radius:5;
		$vendors_ids = User::
			where('privileges',3)
			->select(DB::raw("id, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
			->havingRaw('distance <= '.$radius)
        	->pluck('id');
		if(sizeof($vendors_ids)>0) {
			if(request()->has('item')) {
				$search_by = request()->item;
				$item_vendor_ids = ItemMaster::whereIn('vendors_id',$vendors_ids)
					->where(function($query) use ($search_by){
	                    $query->where('name','like','%'.$search_by.'%')
	                    	->orWhere('brand_name','like','%'.$search_by.'%');
	                })
					->pluck('vendors_id');
				if(sizeof($item_vendor_ids)>0) {
					$vendors = User::
						where('privileges',3)
						->whereIn('id',$item_vendor_ids)
						->select(DB::raw("users.*, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
						->havingRaw('distance <= '.$radius)
						->orderBy('distance')
			        	->get();

			        foreach ($vendors as $key => $vendor) {
			        	$vendor->distance = round($vendor->distance, 2);
			        }
					return response()->json([
		                'errorCode' => 0,
		                'data' => $vendors,
		                'message' => 'Nearby vendors list'
		            ]);
				}
				else {
					return response()->json([
			            'errorCode' => 1,
			            'message' => 'No vendors found in nearby location selling item '.request()->item
			        ]);
				}
			}
			else {
				$vendors = User::
					where('privileges',3)
					->whereIn('id',$vendors_ids)
					->select(DB::raw("users.*, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
					->havingRaw('distance <= '.$radius)
					->orderBy('distance')
		        	->get();
		        foreach ($vendors as $key => $vendor) {
		        	$vendor->distance = round($vendor->distance, 2);
		        }
				return response()->json([
	                'errorCode' => 0,
	                'data' => $vendors,
	                'message' => 'Nearby vendors list'
	            ]);
	        }
		}
		return response()->json([
            'errorCode' => 1,
            'message' => 'No vendors found in nearby location'
        ]);
	}

	public function getNearbyVendors()
	{
		if(!request()->has('lat') || !request()->has('lng')) {
			return response()->json([
                'errorCode' => 1,
                'message' => 'Latitude and Longitude is required'
            ]);
		}
		$user = auth()->guard('api')->user();
		$header = request()->header('lang');
		$lat = request()->lat;
		$lng = request()->lng;
		$radius = (request()->has('radius'))?request()->radius:5;
		$vendors_ids = User::
			where('privileges',3)
			->select(DB::raw("id, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
			->havingRaw('distance <= '.$radius)
        	->pluck('id');
		if(sizeof($vendors_ids)>0) {
			if(request()->has('item')) {
				$search_by = request()->item;
				$item_vendor_ids = VendorItem::
					join('items_master','vendor_items.item_master_id','items_master.id')
					->whereIn('vendor_items.user_id',$vendors_ids)
					->where(function($query) use ($search_by){
	                    $query->where('items_master.name','like','%'.$search_by.'%')
	                    	->orWhere('items_master.brand_name','like','%'.$search_by.'%');
	                })
					->pluck('user_id');
				if(sizeof($item_vendor_ids)>0) {
					$vendors = User::
						where('privileges',3)
						->whereIn('id',$item_vendor_ids)
						// ->select(DB::raw("users.*, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
						->select(DB::raw("users.id, users.name, users.photo, users.lat, users.lng, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"),DB::raw('(SELECT avg(rating) FROM vendor_reviews WHERE vendor_reviews.vendor_id=users.id) AS avg_rating'))
						->havingRaw('distance <= '.$radius)
						->orderBy('distance')
			        	->get();

			        foreach ($vendors as $key => $vendor) {
			        	$vendor->distance = number_format((float)$vendor->distance, 3, '.', '');
			        	$vendor->avg_rating = round($vendor->avg_rating, 2);
			        	$vendor->lat = number_format((float)$vendor->lat, 6, '.', '');
			        	$vendor->lng = number_format((float)$vendor->lng, 6, '.', '');
			        }
					return response()->json([
		                'errorCode' => 0,
		                'data' => $vendors,
		                'message' => 'Nearby vendors list'
		            ]);
				}
				else {
					return response()->json([
			            'errorCode' => 1,
			            'message' => 'No vendors found in nearby location selling item '.request()->item
			        ]);
				}
			}
			else {
				$vendors = User::
					where('privileges',3)
					->whereIn('id',$vendors_ids)
					// ->select(DB::raw("users.*, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
					->select(DB::raw("users.id, users.name, users.photo, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"),DB::raw('(SELECT avg(rating) FROM vendor_reviews WHERE vendor_reviews.vendor_id=users.id) AS avg_rating'))
					->havingRaw('distance <= '.$radius)
					->orderBy('distance')
		        	->get();
		        foreach ($vendors as $key => $vendor) {
		        	$vendor->distance = number_format((float)$vendor->distance, 3, '.', '');
			        $vendor->avg_rating = round($vendor->avg_rating, 2);
		        }
				return response()->json([
	                'errorCode' => 0,
	                'data' => $vendors,
	                'message' => 'Nearby vendors list'
	            ]);
	        }
		}
		return response()->json([
            'errorCode' => 1,
            'message' => 'No vendors found in nearby location'
        ]);
	}

	public function getVendorItems($vendor_id)
	{
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
		// $items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')->where('vendors_id',$vendor_id)->orderBy('name', 'asc')->get();
		// $items = ItemMaster::whereNotNull($column)
		// 	->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')
		// 	->join('vendor_items','vendor_items.item_master_id','items_master.id')
		// 	->join('vendor_item_attributes','vendor_items.item_master_id','items_master.id')
		// 	->where('vendors_id',$vendor_id)
		// 	->orderBy('name', 'asc')
		// 	->get();

		$items = VendorItem::with('item_master')
			->with('vendor_item_attributes')
			->where('user_id',$vendor_id)
			->get();
		$myarr = array();
		if(sizeof($items)>0) {
			foreach ($items as $key => $item) {
				$item_master = ItemMaster::
				select(DB::raw('(SELECT avg(rating) FROM item_master_reviews WHERE item_master_reviews.item_master_id=items_master.id) AS avg_rating'))
				->where('id',$item->item_master_id)
				->first();
				$item_name = $item->item_master->name;
				$item_description = $item->item_master->description;
				$item_brand_name = $item->item_master->brand_name;
				if($header=='hn') {
					$item_name = $item->item_master->name_hn;
					$item_description = $item->item_master->description_hn;
					$item_brand_name = $item->item_master->brand_name_hn;
				}
				else if($header=='mr') {
					$item_name = $item->item_master->name_mr;
					$item_description = $item->item_master->description_mr;
					$item_brand_name = $item->item_master->brand_name_mr;
				}

				if(isset($item_name)) {
					$object = new stdClass;
			        $object->id = $item->id;
					$object->name = $item_name;
					$object->description = $item_description;
					$object->brand_name = $item_brand_name;
					$object->hsn_code = $item->item_master->hsn_code;
					$object->item_categories_id = $item->item_master->item_categories_id;
					$object->item_sub_categories_id = $item->item_master->item_sub_categories_id;
					$object->companies_id = $item->item_master->companies_id;
					$object->image = $item->item_master->image;
					$object->avg_rating = (isset($item_master->avg_rating))?round($item_master->avg_rating,2):0;
					// $object->created_at = "2023-01-13$object->18 =bject->14 =3";
					// $object->updated_at = "2022-12-07$object->22 =bject->03 =0";
					// $object->vendors_id = 1021;
					
					$object->gst = $item->gst;
					$object->discount = $item->discount;
					
					$object->form_id = $item->vendor_item_attributes[0]->form_id;
					$object->price = $item->vendor_item_attributes[0]->price;
					$object->wt_size = $item->vendor_item_attributes[0]->wt_size;
					$object->uom_id = $item->vendor_item_attributes[0]->uom_id;
					
					$attributes = $item->vendor_item_attributes->map(function($attribute, $key) {
			            return [
								'price' => $attribute->price,
								'wt_size' => $attribute->wt_size,
								'form_id' => $attribute->form_id,
								'uom_id' => $attribute->uom_id
							];
			        });
					$object->attributes = $attributes;
					array_push($myarr, $object);
				}
			}
			return response()->json([
                'errorCode' => 0,
                'data' => $myarr,
                'message' => 'Vendor items list'
            ]);
		}
		return response()->json([
            'errorCode' => 1,
            'message' => 'Vendor items not found'
        ]);
	}
	public function getVendorItemDetails($id)
	{
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
		// $items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')->where('vendors_id',$vendor_id)->orderBy('name', 'asc')->get();
		// $items = ItemMaster::whereNotNull($column)
		// 	->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')
		// 	->join('vendor_items','vendor_items.item_master_id','items_master.id')
		// 	->join('vendor_item_attributes','vendor_items.item_master_id','items_master.id')
		// 	->where('vendors_id',$vendor_id)
		// 	->orderBy('name', 'asc')
		// 	->get();

		$item = VendorItem::with('item_master')->where('id',$id)->first();
		$myarr = array();
		if($item) {
			// foreach ($items as $key => $item) {
				$item_master = ItemMaster::
				select(DB::raw('(SELECT avg(rating) FROM item_master_reviews WHERE item_master_reviews.item_master_id=items_master.id) AS avg_rating'))
				->where('id',$item->item_master_id)
				->first();
				$item_name = $item->item_master->name;
				$item_description = $item->item_master->description;
				$item_brand_name = $item->item_master->brand_name;
				if($header=='hn') {
					$item_name = $item->item_master->name_hn;
					$item_description = $item->item_master->description_hn;
					$item_brand_name = $item->item_master->brand_name_hn;
				}
				else if($header=='mr') {
					$item_name = $item->item_master->name_mr;
					$item_description = $item->item_master->description_mr;
					$item_brand_name = $item->item_master->brand_name_mr;
				}

				if(isset($item_name)) {
					$object = new stdClass;
			        $object->id = $item->id;
					$object->name = $item_name;
					$object->description = $item_description;
					$object->brand_name = $item_brand_name;
					$object->hsn_code = $item->item_master->hsn_code;
					$object->item_categories_id = $item->item_master->item_categories_id;
					$object->item_sub_categories_id = $item->item_master->item_sub_categories_id;
					$object->companies_id = $item->item_master->companies_id;
					$object->image = $item->item_master->image;
					$object->avg_rating = (isset($item_master->avg_rating))?round($item_master->avg_rating,2):0;
					// $object->created_at = "2023-01-13$object->18 =bject->14 =3";
					// $object->updated_at = "2022-12-07$object->22 =bject->03 =0";
					// $object->vendors_id = 1021;
					
					$object->gst = $item->gst;
					$object->discount = $item->discount;
					
					$attributes = VendorItemAttributes::with('uom')->with('form')->where('vendor_item_id',$id)->get();
					// $object->form_id = $item->vendor_item_attributes[0]->form_id;
					// $object->price = $item->vendor_item_attributes[0]->price;
					// $object->wt_size = $item->vendor_item_attributes[0]->wt_size;
					// $object->uom_id = $item->vendor_item_attributes[0]->uom_id;
					
					$attributes = $attributes->map(function($attribute, $key) {
			            return [
								'price' => $attribute->price,
								'wt_size' => $attribute->wt_size,
								'form' => $attribute->form->title,
								'uom' => $attribute->uom->uom
							];
			        });
					$object->attributes = $attributes;
					$rating_array = array();
		            for ($i=0; $i < 5; $i++) { 
		                $new_rating_group['rating'] = $i+1;
		                $new_rating_group['total'] = 0;
		                array_push($rating_array, $new_rating_group);
		            }
		            // if(count($item_master->item_master_reviews)) {
		                $rating_group = ItemMasterReview::where('item_master_id',$item->item_master_id)->select('rating', DB::raw('count(rating) as total'))->groupBy('rating')->orderBy('rating')->get();
		                if(count($rating_group)) {
		                    foreach ($rating_group as $key => $value) {
		                        $rating_array[$value->rating-1]['total'] = $value->total;
		                    }
		                }
		            // }
					$object->rating_group = $rating_array;
					array_push($myarr, $object);
				// }
			}
			return response()->json([
                'errorCode' => 0,
                'data' => $myarr,
                'message' => 'Get vendor item details successful'
            ]);
		}
		return response()->json([
            'errorCode' => 1,
            'message' => 'Get vendor item details failed'
        ]);
	}
	public function getCompanyItems()
	{
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
		if(request()->has('item')) {
			$search_by = request()->item;
			// $items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')->where('companies_id',$user->id)->where('name','like','%'.request()->item.'%')->orderBy('name', 'asc')->get();
			$items = ItemMaster::whereNotNull($column)
				->select('id',$lang,$description_lang,'hsn_code','item_categories_id','item_sub_categories_id','created_at','updated_at','companies_id',$brand_name_lang,'image',DB::raw('(SELECT avg(rating) FROM item_master_reviews WHERE item_master_reviews.item_master_id=items_master.id) AS avg_rating'))
				->where('companies_id',$user->id)
				->where(function($query) use ($search_by){
                    $query->where('name','like','%'.$search_by.'%')
                    	->orWhere('brand_name','like','%'.$search_by.'%')
                    	->orWhere('description','like','%'.$search_by.'%');
                })
				->orderBy('name', 'asc')
				->get();
		}
		else {
			// $items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')->where('companies_id',$user->id)->orderBy('name', 'asc')->get();
			$items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'hsn_code','item_categories_id','item_sub_categories_id','created_at','updated_at','companies_id',$brand_name_lang,'image',DB::raw('(SELECT avg(rating) FROM item_master_reviews WHERE item_master_reviews.item_master_id=items_master.id) AS avg_rating'))->where('companies_id',$user->id)->orderBy('name', 'asc')->get();
		}
		if(sizeof($items)>0) {
			$items = $items->map(function($value, $key) {
                if($value->avg_rating==null)
                    $value->avg_rating = 0;
                else
                    $value->avg_rating = round($value->avg_rating,2);
                return $value;
            });
			return response()->json([
                'errorCode' => 0,
                'data' => $items,
                'message' => 'Company items list'
            ]);
		}
		return response()->json([
            'errorCode' => 1,
            'message' => 'Company items not found'
        ]);
	}

	public function getCompanyListold()
	{
		$sort = 'asc';
		if(request()->has('sort'))
			$sort = request()->sort;

		if(request()->has('type') && request()->type=='name') {
			$companies = User::where('privileges',2)->orderBy('name',$sort)->get();
		}
		else if(request()->has('type') && request()->type=='employee_nos') {
			$forum_companies = ForumQuestion::groupBy('company_id')->pluck('company_id');
			$users = User::withCount('child_company')->whereIn('id',$forum_companies)->orderBy('child_company_count',$sort)->get();
			// dd($users->toArray());
		}
		else if(request()->has('type') && request()->type=='question_asked') {
			// $forum_companies = ForumQuestion::get();
			// $forum_companies_count = $forum_companies->groupBy('company_id')->map->count()->toArray();
			$forum_companies = ForumQuestion::
				select('company_id', DB::raw('count(company_id) as question_asked_count'))
				->groupBy('company_id')
				->orderBy(DB::raw('COUNT(company_id)'), 'DESC')
				->get();
			dd($forum_companies->toArray());
			// $forum_companies_list = $forum_companies->groupBy('company_id');
			// dd($forum_companies_list->toArray());
			foreach ($forum_companies_list as $key => $company) {
				$company->ques_count = (isset($forum_companies_count[$key]))?$forum_companies_count[$key]:0;
			}
			dd($forum_companies_list->toArray());
			$users = User::whereIn('id',$forum_companies)->orderBy('child_company_count',$sort)->get();
			dd($users->toArray());
		}
		
		if(sizeof($companies)>0) {
			return response()->json([
                'errorCode' => 0,
                'data' => $companies,
                'message' => 'Get company list successful'
            ]);
		}
		return response()->json([
            'errorCode' => 1,
            'message' => 'Get company list failed'
        ]);
	}
	public function getCompanyList()
	{
		$companies = array();
		if(request()->has('filter') && request()->filter=='name_asc') {
			$companies = User::select('id','name')->where('privileges',2)->orderBy('name','asc')->get();
		}
		else if(request()->has('filter') && request()->filter=='name_desc') {
			$companies = User::select('id','name')->where('privileges',2)->orderBy('name','desc')->get();
		}
		else if(request()->has('filter') && request()->filter=='employee_nos_asc') {
			$forum_companies = ForumQuestion::groupBy('company_id')->pluck('company_id');
			$companies = User::select('id','name')->withCount('child_company')->whereIn('id',$forum_companies)->orderBy('child_company_count','asc')->get();
		}
		else if(request()->has('filter') && request()->filter=='employee_nos_desc') {
			$forum_companies = ForumQuestion::groupBy('company_id')->pluck('company_id');
			$companies = User::select('id','name')->withCount('child_company')->whereIn('id',$forum_companies)->orderBy('child_company_count','desc')->get();
		}
		else if(request()->has('filter') && request()->filter=='question_asked_asc') {
			$companies = ForumQuestion::
				select('forum_questions.company_id as id', 'name')
				->join('users','users.id','forum_questions.company_id')
				->groupBy('forum_questions.company_id')
				->orderBy(DB::raw('COUNT(forum_questions.company_id)'), 'asc')
				->get();
		}
		else if(request()->has('filter') && request()->filter=='question_asked_desc') {
			$companies = ForumQuestion::
				select('forum_questions.company_id as id', 'name')
				->join('users','users.id','forum_questions.company_id')
				->groupBy('forum_questions.company_id')
				->orderBy(DB::raw('COUNT(forum_questions.company_id)'), 'desc')
				->get();
		}
		
		if(sizeof($companies)>0) {
			return response()->json([
                'errorCode' => 0,
                'data' => $companies,
                'message' => 'Get company list successful'
            ]);
		}
		return response()->json([
            'errorCode' => 1,
            'message' => 'Get company list failed'
        ]);
	}

	public function getShopItemsByCategory($category_id)
	{
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
		$items = ShopItem::whereNotNull($column)
			->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','gst','created_at','updated_at','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')
			->where('item_categories_id',$category_id);
		if(request()->has('sort_by')) {
            $sort_by = request()->sort_by;
            if($sort_by=='name_asc')
                $items = $items->orderBy('name', 'asc');
            else if($sort_by=='name_desc')
                $items = $items->orderBy('name', 'desc');
            else if($sort_by=='price_asc')
                $items = $items->orderBy('price', 'asc');
            else if($sort_by=='price_desc')
                $items = $items->orderBy('price', 'desc');
        }
        else
			$items = $items->orderBy('name', 'asc');
		$items = $items->get();
		if(count($items)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $items,
                'message' => 'Get items successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get items failed'
        ]);
	}

	public function getCompanyForumStats()
	{
		$user = auth()->guard('api')->user();

		$company_ids = ForumQuestion::where('company_id',$user->id)->pluck('id');
		if($company_ids) {
			$companies = ForumAnswer::whereIn('forum_question_id',$company_ids)
				->select('forum_question_id',DB::raw('COUNT(forum_answers.id) as tt'))
				->where('company_id',$user->id)
				->groupBy('forum_question_id')
				->get();

	        
			if($companies) {
				$object = new stdClass;
				$object->question_asked_count = count($company_ids);
				$object->question_answered_count = count($companies);
				$object->question_pending_count = count($company_ids)-count($companies);
				
				return response()->json([
	                'errorCode' => 0,
	                'data' => $object,
	                'message' => 'Get company forum stats successful'
	            ]);
			}
		}
		return response()->json([
            'errorCode' => 1,
            'message' => 'Get company forum stats failed'
        ]);
	}

	public function getItemsByCompanyCategoryGroup($company_id, $category_group_id)
	{
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
		// $items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'price','hsn_code','item_categories_id','item_sub_categories_id','sgst','cgst','created_at','updated_at','vendors_id','companies_id','form_id',$brand_name_lang,'wt_size','discount','image','uom_id')->where('item_sub_categories_id',$sub_cat_id)->orderBy('name', 'asc')->get();
		$items = ItemMaster::whereNotNull($column)->select('id',$lang,$description_lang,'hsn_code','item_category_group_id','item_categories_id','item_sub_categories_id','created_at','updated_at','companies_id',$brand_name_lang,'image')->where('item_category_group_id',$category_group_id)->where('companies_id',$company_id)->orderBy('name', 'asc')->get();
		if(count($items)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $items,
                'message' => 'Get items successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get items failed'
        ]);
	}

	public function getItemDetailsOld($id)
	{
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
		$item = ItemMaster::whereNotNull($column)
			->select('id',$lang,$description_lang,'hsn_code','item_categories_id','item_sub_categories_id','created_at','updated_at','companies_id',$brand_name_lang,'image')
			->where('id',$id)
			->first();
		if($item) {
            return response()->json([
                'errorCode' => 0,
                'data' => $item,
                'message' => 'Get item details successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get item details failed'
        ]);
	}

	public function getItemDetailsold2($id)
	{
		if(!request()->has('lat') || !request()->has('lng')) {
			return response()->json([
                'errorCode' => 1,
                'message' => 'Latitude and Longitude is required'
            ]);
		}
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
		$itemMaster = ItemMaster::with('category_group')->where('id',$id)->first();
		if(!$itemMaster) {
			return response()->json([
	            'errorCode' => 1,
	            'message' => 'Item id is not valid'
	        ]);
		}
		
		$lat = request()->lat;
		$lng = request()->lng;
		$radius = (isset($itemMaster) && isset($itemMaster->category_group) && isset($itemMaster->category_group->vendor_requisition_distance))?$itemMaster->category_group->vendor_requisition_distance:10;
		$vendors_ids = User::
			where('privileges',3)
			->where('is_subscribed',1)
			->select(DB::raw("id, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
			->havingRaw('distance <= '.$radius)
        	->pluck('id');
		if(sizeof($vendors_ids)>0) {
			$vendor_items = ItemMaster::
				join('vendor_items','vendor_items.item_master_id','items_master.id')
				->whereIn('vendor_items.user_id',$vendors_ids)
				->whereNotNull($column)
				->where('items_master.id',$id)
				// ->select('items_master.id',$lang,$description_lang,'hsn_code','item_categories_id','item_sub_categories_id','items_master.created_at','items_master.updated_at','companies_id',$brand_name_lang,'image','vendor_items.id AS vendor_item_id','vendor_items.user_id AS vendors_id')
				->select('items_master.id',$lang,$description_lang,$brand_name_lang,'image','vendor_items.id AS vendor_item_id','vendor_items.user_id AS vendors_id')
				->orderBy('name', 'asc')
				->get();
				// ->pluck('vendor_items.id');
	        if(count($vendor_items)) {
	        	$vendor_item_ids = $vendor_items->pluck('vendor_item_id');
	        	$attributesArr = array();
	        	$vendor_item_attributes = VendorItemAttributes::with('uom')->whereIn('vendor_item_id',$vendor_item_ids)->get();
		        if(count($vendor_item_attributes)) {
		        	foreach ($vendor_item_attributes as $key => $value) {
		        		// $attribute['id'] = $value->id;
		        		// $attribute['uom'] = $value->uom->uom;
		        		// $attribute['size'] = $value->wt_size;
		        		// array_push($attributesArr, $attribute);
		        		if(!in_array($value->wt_size." ".$value->uom->uom, $attributesArr)) {
		        			$attribute['id'] = $value->wt_size."|".$value->uom_id;
		        			$attribute['value'] = $value->wt_size." ".$value->uom->uom;
		        			array_push($attributesArr, $attribute);
		        		}
		        	}
		        }

	        	$vendor_item = $vendor_items->first();
	        	$vendor_item->attributes = $attributesArr;
		        return response()->json([
	                'errorCode' => 0,
	                'data' => $vendor_item,
	                'message' => 'Get item details successful'
	            ]);
	        }
		}
		return response()->json([
            'errorCode' => 1,
            'message' => 'Get item details failed'
        ]);
	}

	public function getItemDetails($id)
	{
		if(!request()->has('lat') || !request()->has('lng')) {
			return response()->json([
                'errorCode' => 1,
                'message' => 'Latitude and Longitude is required'
            ]);
		}
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
		$itemMaster = ItemMaster::with('category_group')->where('id',$id)->first();
		if(!$itemMaster) {
			return response()->json([
	            'errorCode' => 1,
	            'message' => 'Item id is not valid'
	        ]);
		}
		
		$lat = request()->lat;
		$lng = request()->lng;
		$radius = (isset($itemMaster) && isset($itemMaster->category_group) && isset($itemMaster->category_group->vendor_requisition_distance))?$itemMaster->category_group->vendor_requisition_distance:10;
		$vendors = User::
			where('privileges',3)
			->where('is_subscribed',1)
			->select(DB::raw("id, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
			->havingRaw('distance <= '.$radius)
        	->get();
        	// ->pluck('id');
		if(sizeof($vendors)>0) {
			$vendors_ids = array();
			foreach ($vendors as $key => $vendor) {
                $allowed_requisition = true;
                $vendor_requisitions_count = 0;
                $vendorRequisitions = VendorRequisition::where('user_id',$vendor->id)->count();
                if($vendorRequisitions==0 || $vendorRequisitions>0) {
                    $vendor_requisitions_count = $vendorRequisitions;
                    $subscription_value = $this->getSubscriptionDetails('requisition_nos',$vendor->id);
                    if($subscription_value!="-1") {
                        $allowed_items_count = $subscription_value;
                        $special_addon_value = $this->getSpecialAddonDetails(2,$vendor->id);
                        if(isset($special_addon_value))
                            $allowed_items_count += $special_addon_value;
                        if($subscription_value==null)
                            $allowed_requisition = false;
                        else if($vendor_requisitions_count>=$allowed_items_count)
                            $allowed_requisition = false;
                    }
                    if($allowed_requisition)
                    	array_push($vendors_ids, $vendor->id);
                }
            }
            if(count($vendors_ids)==0) {
            	return response()->json([
		            'errorCode' => 1,
		            'message' => 'Vendor details not available for selected item'
		        ]);
            }
            $vendor_items = ItemMaster::
				join('vendor_items','vendor_items.item_master_id','items_master.id')
				->whereIn('vendor_items.user_id',$vendors_ids)
				->whereNotNull($column)
				->where('items_master.id',$id)
				// ->select('items_master.id',$lang,$description_lang,'hsn_code','item_categories_id','item_sub_categories_id','items_master.created_at','items_master.updated_at','companies_id',$brand_name_lang,'image','vendor_items.id AS vendor_item_id','vendor_items.user_id AS vendors_id')
				->select('items_master.id',$lang,$description_lang,$brand_name_lang,'image','vendor_items.id AS vendor_item_id','vendor_items.user_id AS vendors_id')
				->orderBy('name', 'asc')
				->get();
				// ->pluck('vendor_items.id');
	        if(count($vendor_items)) {
	        	$vendor_item_ids = $vendor_items->pluck('vendor_item_id');
	        	$attributesArr = array();
	        	$vendor_item_attributes = VendorItemAttributes::with('uom')->whereIn('vendor_item_id',$vendor_item_ids)->get();
		        if(count($vendor_item_attributes)) {
		        	foreach ($vendor_item_attributes as $key => $value) {
		        		// $attribute['id'] = $value->id;
		        		// $attribute['uom'] = $value->uom->uom;
		        		// $attribute['size'] = $value->wt_size;
		        		// array_push($attributesArr, $attribute);
		        		if(!in_array($value->wt_size." ".$value->uom->uom, $attributesArr)) {
		        			$attribute['id'] = $value->wt_size."|".$value->uom_id;
		        			$attribute['value'] = $value->wt_size." ".$value->uom->uom;
		        			array_push($attributesArr, $attribute);
		        		}
		        	}
		        }

	        	$vendor_item = $vendor_items->first();
	        	$vendor_item->attributes = $attributesArr;
		        return response()->json([
	                'errorCode' => 0,
	                'data' => $vendor_item,
	                'message' => 'Get item details successful'
	            ]);
	        }
		}
		return response()->json([
            'errorCode' => 1,
            'message' => 'Get item details failed'
        ]);
	}

	public function getSearchNearbyItems()
	{
		if(!request()->has('lat') || !request()->has('lng')) {
			return response()->json([
                'errorCode' => 1,
                'message' => 'Latitude and Longitude is required'
            ]);
		}
		if(!request()->has('item')) {
			return response()->json([
                'errorCode' => 1,
                'message' => 'Item name is required'
            ]);
		}
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
		$search_by = request()->item;
		$lat = request()->lat;
		$lng = request()->lng;
		$radius = (request()->has('radius'))?request()->radius:10;
		$vendors = User::
			where('privileges',3)
			->where('is_subscribed',1)
			->select('id',DB::raw("id, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
			->havingRaw('distance <= '.$radius)
        	->get('id');
        	// ->pluck('id');
        if(sizeof($vendors)>0) {
			$vendors_ids = array();
			foreach ($vendors as $key => $vendor) {
                $allowed_requisition = true;
                $vendor_requisitions_count = 0;
                $vendorRequisitions = VendorRequisition::where('user_id',$vendor->id)->count();
                if($vendorRequisitions==0 || $vendorRequisitions>0) {
                    $vendor_requisitions_count = $vendorRequisitions;
                    $subscription_value = $this->getSubscriptionDetails('requisition_nos',$vendor->id);
                    if($subscription_value!="-1") {
                        $allowed_items_count = $subscription_value;
                        $special_addon_value = $this->getSpecialAddonDetails(2,$vendor->id);
                        if(isset($special_addon_value))
                            $allowed_items_count += $special_addon_value;
                        if($subscription_value==null)
                            $allowed_requisition = false;
                        else if($vendor_requisitions_count>=$allowed_items_count)
                            $allowed_requisition = false;
                    }
                    if($allowed_requisition)
                    	array_push($vendors_ids, $vendor->id);
                }
            }
            if(count($vendors_ids)==0) {
            	return response()->json([
		            'errorCode' => 1,
		            'message' => 'No items found in nearby location'
		        ]);
            }
			$vendor_items = ItemMaster::
				join('vendor_items','vendor_items.item_master_id','items_master.id')
				->whereIn('vendor_items.user_id',$vendors_ids)
				->whereNotNull($column)
				// ->select('items_master.id',$lang,$description_lang,'hsn_code','item_categories_id','item_sub_categories_id','items_master.created_at','items_master.updated_at','companies_id',$brand_name_lang,'image')
				->select('items_master.id',$lang,$description_lang,$brand_name_lang,'image')
				// ->where('item_sub_categories_id',$sub_cat_id)
				->where(function($query) use ($search_by){
                    $query->where('name','like','%'.$search_by.'%')
                    	->orWhere('brand_name','like','%'.$search_by.'%')
                    	->orWhere('description','like','%'.$search_by.'%');
                })
				->orderBy('name', 'asc')
				->groupBy('items_master.id')
				->get();
	        if(count($vendor_items)) {
		        return response()->json([
	                'errorCode' => 0,
	                'data' => $vendor_items,
	                'message' => 'Get nearby items successful'
	            ]);
	        }
		}
		return response()->json([
            'errorCode' => 1,
            'message' => 'No items found in nearby location'
        ]);
	}
	public function getCompanyArea()
	{
		$user = auth()->guard('api')->user();
		$header = request()->header('lang');
		$lang = 'name';
		if($header=='hn') {
			$lang = 'name_hn as name';
		}
		else if($header=='mr') {
			$lang = 'name_mr as name';
		}
		if($user->management_level==1 || $user->management_level==2 || $user->management_level==3) {
			$company_area = CompanyUserArea::select('company_user_area.id',$lang);
			if($user->management_level==1) {
					$company_area = $company_area->join('states','company_user_area.state_id','states.id');
			}
			else if($user->management_level==2) {
					$company_area = $company_area->join('districts','company_user_area.district_id','districts.id');
			}
			else if($user->management_level==3) {
					$company_area = $company_area->join('talukas','company_user_area.taluka_id','talukas.id');
			}
			$company_area = $company_area->where('company_user_area.user_id',$user->id)
				->where('company_user_area.management_level',$user->management_level)
				->pluck($lang);
			
			if($company_area) {
	            return response()->json([
	                'errorCode' => 0,
	                'data' => $company_area,
	                'message' => 'Get company area successful'
	            ]);
	        }
	    }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get company area failed'
        ]);
	}
}
