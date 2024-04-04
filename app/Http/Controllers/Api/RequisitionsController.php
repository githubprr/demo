<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Requisitions;
use App\RequisitionItems;
use App\ItemMaster;
use App\User;
use App\Settings;
use App\CategoryGroupMaster;
use App\VendorRequisition;
use App\VendorItem;
use App\UserNotification;
use App\Enquiries;
use App\GroupUser;
use Auth;

class RequisitionsController extends Controller
{
    public function create(Request $request)
    {
        $valid = validator($request->only('required_on', 'items', 'lat', 'lng', 'category_group_id', 'group_id'), [
            'required_on' => 'required',
            'items' => 'required',
            'lat' => 'required',
            'lng' => 'required'
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $data = request()->only('remarks', 'required_on', 'items', 'lat', 'lng', 'category_group_id', 'group_id');

        $category_group_id = null;
        if(isset($data['category_group_id'])) {
            $category_group_id = $data['category_group_id'];
        }
        else {
            $item_master = ItemMaster::where('id',$data['items'][0]['item_master_id'])->first();
            if($item_master)
                $category_group_id = $item_master->item_category_group_id;
        }

        $group_id = null;
        if(isset($data['group_id'])) {
            $group_id = $data['group_id'];
        }

        $uom_id = null;
        $wt_size = null;
        $size_unit_text = null;
        foreach ($data['items'] as $key => $item) {
            $uom_id = null;
            $wt_size = null;
            $size_unit_text = null;

            if(isset($item['size_unit'])) {
                $size_unit = explode('|', $item['size_unit']);
                $uom_id = $size_unit[1];
                $wt_size = $size_unit[0];
            }
            if(isset($item['size_unit_text']))
                $size_unit_text = $item['size_unit_text'];
        }

        // TESTING
        // $setting = Settings::where('label','vendor_requisition_distance')->first();
        $setting = CategoryGroupMaster::where('id',$category_group_id)->first();
        $lat = $data['lat'];
        $lng = $data['lng'];
        $radius = (isset($setting))?$setting->vendor_requisition_distance:10;
        $vendors = User::
            where('privileges',3)
            ->where('is_subscribed',1)
            ->select(DB::raw("id, category_group_id, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
            ->havingRaw('distance <= '.$radius)
            ->where('category_group_id',$category_group_id)
            ->orderBy('distance')
            ->get();
            // ->pluck('id');
        // dd($vendors->toArray());

        $vendors_ids = array();
        if(count($vendors)) {
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
                }
                if($allowed_requisition) {
                    $vendorRequisitionItem = VendorItem::with('vendor_item_attributes')->where('user_id',$vendor->id)->where('item_master_id',$data['items'][0]['item_master_id'])->first();
                    if($vendorRequisitionItem) {
                        if(count($vendorRequisitionItem->vendor_item_attributes)) {
                            $is_attribute_exists = $vendorRequisitionItem->vendor_item_attributes->where('uom_id',$uom_id)->where('wt_size',$wt_size)->first();
                            if($is_attribute_exists) {
                                array_push($vendors_ids, $vendor->id);
                            }
                        }
                    }
                }
            }
        }
        if(count($vendors_ids)==0) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'No any vendor found nearby who can fulfil your requisition'
            ]);
        }
        // TESTING

        $requisition = Requisitions::create([
            'remarks' => urldecode($data['remarks']),
            'required_on' => $data['required_on'],
            'user_id' => Auth::user()->id,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'category_group_id' => $category_group_id,
            'group_id' => $group_id
        ]);
		$requisition->reference_requisition_no = 'PSRQ' . $requisition->id;
        $requisition->save();

        
        foreach ($data['items'] as $key => $item) {
            $uom_id = null;
            $wt_size = null;
            $size_unit_text = null;
            
            if(isset($item['size_unit'])) {
                $size_unit = explode('|', $item['size_unit']);
                $uom_id = $size_unit[1];
                $wt_size = $size_unit[0];
            }
            if(isset($item['size_unit_text']))
                $size_unit_text = $item['size_unit_text'];
            
            $requisition_item = RequisitionItems::create([
                'item_master_id' => $item['item_master_id'],
                'item_name' => urldecode($item['item_name']),
                'item_description' => urldecode($item['item_description']),
                'item_best_price' => $item['item_best_price'],            
                'qty' => $item['qty'],
                'requisition_id' => $requisition->id,
                'uom_id' => $uom_id,
                'wt_size' => $wt_size,
                'size_unit_text' => $size_unit_text
            ]);
            $requisition_item->save();
        }

        // TESTING
        if(count($vendors)) {
            foreach ($vendors as $key => $vendor) {
                $vendorRequisitionItem = VendorItem::with('vendor_item_attributes')->where('user_id',$vendor->id)->where('item_master_id',$data['items'][0]['item_master_id'])->first();
                if($vendorRequisitionItem) {
                    if(count($vendorRequisitionItem->vendor_item_attributes)) {
                        $is_attribute_exists = $vendorRequisitionItem->vendor_item_attributes->where('uom_id',$uom_id)->where('wt_size',$wt_size)->first();
                        if($is_attribute_exists) {
                            $vendorRequisition = new VendorRequisition;
                            $vendorRequisition->user_id = $vendor->id;
                            $vendorRequisition->requisition_id = $requisition->id;
                            $vendorRequisition->distance = round($vendor->distance,2);
                            $vendorRequisition->save();

                            $notification = new UserNotification;
                            $notification->notification = "You have received new requisition : ".$requisition->reference_requisition_no;
                            $notification->notification_hn = "आपको नया अनुरोध प्राप्त हुआ है : ".$requisition->reference_requisition_no;
                            $notification->notification_mr = "तुम्हाला नवीन मागणी मिळाली आहे : ".$requisition->reference_requisition_no;
                            $notification->user_id = $vendor->id;
                            $notification->read_status = 0;
                            $notification->save();
                            $user_ids = array();
                            array_push($user_ids, $vendor->id);
                            $this->sendFCMNotification($user_ids,urldecode($data['remarks']),null,$requisition->id,'requisition');
                        }
                    }
                }
            }
        }
        // TESTING
                
        if($requisition) {
            return response()->json([
                'errorCode' => 0,
                'data' => $requisition,
                'message' => 'Requisition added successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Requisition add failed'
        ]);
    }

    public function createold(Request $request)
    {
        $valid = validator($request->only('required_on', 'items', 'lat', 'lng', 'category_group_id'), [
            'required_on' => 'required',
            'items' => 'required',
            'lat' => 'required',
            'lng' => 'required'
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $data = request()->only('remarks', 'required_on', 'items', 'lat', 'lng', 'category_group_id');
        

        // $item = Items::where('name', $data['item_name'])->firstOrNew();
        // $item->name = $data['item_name'];
        // $item->hsn_code = $data['hsn_code'];
        // if(empty($item->id)) {
        //     $item->description = $data['item_description'];
        //     $item->price = $data['item_best_price'];
        // }
        // $item->save();
        
        // if($request->hasFile('file')){
        //     //Storage::delete('/public/avatars/'.$user->avatar);
        //     $filenameWithExt = $request->file('file')->getClientOriginalName();
        //     $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
        //     $extension = $request->file('file')->getClientOriginalExtension();
        //     $fileNameToStore = $filename.'_'.time().'.'.$extension;
        //     $path = $request->file('file')->storeAs('public/req-attachment',$fileNameToStore);
        // }

        $category_group_id = null;
        if(isset($data['category_group_id'])) {
            $category_group_id = $data['category_group_id'];
        }
        else {
            $item_master = ItemMaster::where('id',$data['items'][0]['item_master_id'])->first();
            if($item_master)
                $category_group_id = $item_master->item_category_group_id;
        }

        $requisition = Requisitions::create([
            'remarks' => urldecode($data['remarks']),
            'required_on' => $data['required_on'],
            'user_id' => Auth::user()->id,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'category_group_id' => $category_group_id
        ]);
        $requisition->reference_requisition_no = 'PSRQ' . $requisition->id;
        $requisition->save();

        $uom_id = null;
        $wt_size = null;
        $size_unit_text = null;
        foreach ($data['items'] as $key => $item) {
            $uom_id = null;
            $wt_size = null;
            $size_unit_text = null;
            
            if(isset($item['size_unit'])) {
                $size_unit = explode('|', $item['size_unit']);
                $uom_id = $size_unit[1];
                $wt_size = $size_unit[0];
            }
            if(isset($item['size_unit_text']))
                $size_unit_text = $item['size_unit_text'];
            
            $requisition_item = RequisitionItems::create([
                'item_master_id' => $item['item_master_id'],
                'item_name' => urldecode($item['item_name']),
                'item_description' => urldecode($item['item_description']),
                'item_best_price' => $item['item_best_price'],            
                'qty' => $item['qty'],
                'requisition_id' => $requisition->id,
                'uom_id' => $uom_id,
                'wt_size' => $wt_size,
                'size_unit_text' => $size_unit_text
            ]);
            $requisition_item->save();
        }
        
        // TESTING
        // $setting = Settings::where('label','vendor_requisition_distance')->first();
        $setting = CategoryGroupMaster::where('id',$category_group_id)->first();
        $lat = $data['lat'];
        $lng = $data['lng'];
        $radius = (isset($setting))?$setting->vendor_requisition_distance:10;
        $vendors = User::
            where('privileges',3)
            ->where('is_subscribed',1)
            ->select(DB::raw("id, category_group_id, ( 6371 * acos( cos( radians('$lat') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('$lng') ) + sin( radians('$lat') ) * sin( radians( lat ) ) ) ) AS distance"))
            ->havingRaw('distance <= '.$radius)
            ->where('category_group_id',$category_group_id)
            ->orderBy('distance')
            ->get();
            // ->pluck('id');
        // dd($vendors->toArray());

        if(count($vendors)) {
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
                }
                
                if($allowed_requisition) {
                    $vendorRequisitionItem = VendorItem::with('vendor_item_attributes')->where('user_id',$vendor->id)->where('item_master_id',$data['items'][0]['item_master_id'])->first();
                    if($vendorRequisitionItem) {
                        if(count($vendorRequisitionItem->vendor_item_attributes)) {
                            $is_attribute_exists = $vendorRequisitionItem->vendor_item_attributes->where('uom_id',$uom_id)->where('wt_size',$wt_size)->first();
                            if($is_attribute_exists) {
                                $vendorRequisition = new VendorRequisition;
                                $vendorRequisition->user_id = $vendor->id;
                                $vendorRequisition->requisition_id = $requisition->id;
                                $vendorRequisition->distance = round($vendor->distance,2);
                                $vendorRequisition->save();

                                $notification = new UserNotification;
                                $notification->notification = "You have received new requisition : ".$requisition->reference_requisition_no;
                                $notification->notification_hn = "आपको नया अनुरोध प्राप्त हुआ है : ".$requisition->reference_requisition_no;
                                $notification->notification_mr = "तुम्हाला नवीन मागणी मिळाली आहे : ".$requisition->reference_requisition_no;
                                $notification->user_id = $vendor->id;
                                $notification->read_status = 0;
                                $notification->save();
                            }
                        }
                    }
                }
            }
        }
        // TESTING
        
        if($requisition) {
            return response()->json([
                'errorCode' => 0,
                'data' => $requisition,
                'message' => 'Requisition added successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Requisition add failed'
        ]);
    }
	
	public function retrieve(Request $request, $id)
    {
		$requisition = Requisitions::with('requisition_items.uom', 'user')->find($id);
		
		if($requisition) {
            if(count($requisition->requisition_items)) {
                $requisition = $requisition->requisition_items->map(function($value, $key) {
                    // if(isset($value->size_unit_text) && isset($value->wt_size)) {
                    //     $size_unit_text_arr = explode(' ', $value->size_unit_text);
                    //     $value->total_size_unit_text = ($value->qty * $value->wt_size) . ' ' . $size_unit_text_arr[1];
                    // }
                    if(isset($value->uom_id) && isset($value->wt_size)) {
                        $size_unit_text_arr = $value->uom->uom;
                        $value->total_size_unit_text = ($value->qty * $value->wt_size) . ' ' . $size_unit_text_arr;
                    }
                    return $value;
                });
            }
            return response()->json([
                'errorCode' => 0,
                'data' => $requisition,
                'message' => 'Get requisition successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get requisition failed'
        ]);
    }
	
	public function update(Request $request, $id)
    {
        $valid = validator($request->only('required_on', 'items'), [
            'required_on' => 'required',
            'items' => 'required'
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $data = request()->only('remarks', 'required_on', 'items');
        
        // $item = Items::where('name', $data['item_name'])->firstOrNew();
        // $item->name = $data['item_name'];
        // $item->hsn_code = $data['hsn_code'];
        // if(empty($item->id)) {
        //     $item->description = $data['item_description'];
        //     $item->price = $data['item_best_price'];
        // }
        // $item->save();
        
        // if($request->hasFile('file')){
        //     //Storage::delete('/public/avatars/'.$user->avatar);
        //     $filenameWithExt = $request->file('file')->getClientOriginalName();
        //     $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
        //     $extension = $request->file('file')->getClientOriginalExtension();
        //     $fileNameToStore = $filename.'_'.time().'.'.$extension;
        //     $path = $request->file('file')->storeAs('public/req-attachment',$fileNameToStore);
        // }

        // $requisition = Requisitions::create([
        //     'remarks' => $data['remarks'],
        //     'required_on' => $data['required_on'],
        //     'user_id' => Auth::user()->id
        // ]);

        Requisitions::where('id', $id)->update([
            'remarks' => $data['remarks'],
            'required_on' => $data['required_on'],
            'user_id' => Auth::user()->id
        ]);

        // $requisition->reference_requisition_no = 'PSRQ' . $requisition->id;
        // $requisition->save();

        foreach ($data['items'] as $key => $item) {
            $requisition_item = RequisitionItems::where('id', $item['id'])->update([
                'item_master_id' => $item['item_master_id'],
                'item_name' => $item['item_name'],
                'item_description' => $item['item_description'],
                'item_best_price' => $item['item_best_price'],            
                'qty' => $item['qty'],
                'requisition_id' => $id
            ]);
            // $requisition_item->save();
        }

        $requisition = Requisitions::with('requisition_items', 'user')->find($id);
        
        if($requisition) {
            return response()->json([
                'errorCode' => 0,
                'data' => $requisition,
                'message' => 'Requisition updated successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Requisition update failed'
        ]);
    }
	
	public function retrieveRequisitionsBasedOnUser(Request $request)
    {
		$requisitions = Requisitions::select('requisitions.id','reference_requisition_no','remarks','required_on')
            ->where('user_id', Auth::user()->id)
            ->orderBy('requisitions.id', 'desc')
            ->get();
		if(count($requisitions)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $requisitions,
                'message' => 'Get requisitions successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get requisitions failed'
        ]);
    }
	
	public function retrieveAll(Request $request)
    {
        // $requisitions = Requisitions::with('requisition_items', 'user')->orderBy('id', 'desc')->get();
		$user_ids = User::where('district_id',Auth::user()->district_id)->pluck('id');
        $requisitions = Requisitions::with('requisition_items', 'user')
            ->whereIn('user_id', $user_ids)
            ->orderBy('id', 'desc')
            ->get();
		
		if(count($requisitions)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $requisitions,
                'message' => 'Get requisitions successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get requisitions failed'
        ]);
    }

    public function requisitionListold()
    {
        $user_ids = User::where('district_id',Auth::user()->district_id)->pluck('id');
        $requisitions = Requisitions::select('requisitions.id','reference_requisition_no','remarks','required_on','users.name')
            ->join('users','requisitions.user_id','users.id')
            ->whereIn('user_id', $user_ids)
            ->orderBy('requisitions.id', 'desc')
            ->get();
        
        if(count($requisitions)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $requisitions,
                'message' => 'Get requisitions successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get requisitions failed'
        ]);
    }

    public function requisitionList()
    {
        $user = auth()->guard('api')->user();
        $ids = VendorRequisition::where('user_id',$user->id)->pluck('requisition_id');
        $requisitions = Requisitions::select('requisitions.id','reference_requisition_no','remarks','required_on','users.name','groups.group_name')
            ->join('users','requisitions.user_id','users.id')
            ->leftJoin('groups','requisitions.group_id','groups.id')
            ->whereIn('requisitions.id', $ids)
            ->orderBy('requisitions.id', 'desc')
            ->get();
        
        if(count($requisitions)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $requisitions,
                'message' => 'Get requisitions successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get requisitions failed'
        ]);
    }

    public function requisitionItemsById($id)
    {
        $user = auth()->guard('api')->user();
        $requisition_items = RequisitionItems::with('uom')->where('requisition_id',$id)->get();
        
        if(count($requisition_items)) {
            $requisition_items = $requisition_items->map(function($value, $key) {
                // if(isset($value->size_unit_text) && isset($value->wt_size)) {
                //     $size_unit_text_arr = explode(' ', $value->size_unit_text);
                //     $value->total_size_unit_text = ($value->qty * $value->wt_size) . ' ' . $size_unit_text_arr[1];
                // }
                if(isset($value->uom_id) && isset($value->wt_size)) {
                    $size_unit_text_arr = $value->uom->uom;
                    $value->total_size_unit_text = ($value->qty * $value->wt_size) . ' ' . $size_unit_text_arr;
                }
                return $value;
            });
            $enquiry_exists = Enquiries::where('requisition_id',$id)->where('user_id',$user->id)->exists();
            if($enquiry_exists)
                $requisition_items[0]->enquiry_exists = 1;
            else
                $requisition_items[0]->enquiry_exists = 0;
            
            return response()->json([
                'errorCode' => 0,
                'data' => $requisition_items,
                'message' => 'Get requisition items successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get requisition items failed'
        ]);
    }

    public function retrieveRequisitionsBasedOnGroup(Request $request)
    {
        $user = auth()->guard('api')->user();
        $group_user = GroupUser::where('user_id',$user->id)->select('group_id')->first();
        if($group_user) {
            $requisitions = Requisitions::select('requisitions.id','reference_requisition_no','remarks','required_on')
                ->where('group_id', $group_user->group_id)
                ->orderBy('requisitions.id', 'desc')
                ->get();
            if(count($requisitions)) {
                return response()->json([
                    'errorCode' => 0,
                    'data' => $requisitions,
                    'message' => 'Get requisitions successful'
                ]);
            }
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get requisitions failed'
        ]);
    }

    public function delivery($id)
    {
        $user = auth()->guard('api')->user();
        $requisition = Requisitions::where('id',$id)->first();
        if($requisition) {
            $otp = rand(100000,999999);
            $requisition->otp = $otp;
            if($requisition->save()) {
                $user_ids = array();
                array_push($user_ids, $requisition->user_id);
                $this->sendFCMNotification($user_ids,'Your requisition OTP is '.$otp,null,$requisition->id,'requisition_otp');
        
                return response()->json([
                    'errorCode' => 0,
                    'message' => 'Requisition delivery OTP sent successful'
                ]);
            }
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Requisition delivery OTP send failed'
        ]);
    }

    public function verify_otp(Request $request)
    {
        $user = auth()->guard('api')->user();
        $requisition = Requisitions::where('id',$request->id)->first();
        if($requisition) {
            if($requisition->otp==$request->otp) {
                $requisition->status = 2;
                $requisition->save();
                
                $user_ids = array();
                array_push($user_ids, $requisition->user_id);
                $this->sendFCMNotification($user_ids,'Requisition delivered successful',null,$requisition->id,'requisition');
        
                return response()->json([
                    'errorCode' => 0,
                    'message' => 'Requisition delivered successful'
                ]);
            }
            return response()->json([
                'errorCode' => 1,
                'message' => 'Invalid OTP'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Requisition delivery failed'
        ]);
    }
}
