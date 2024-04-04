<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Enquiries;
use App\Requisitions;
use App\PointMaster;
use App\PointHistory;
use Auth;

class EnquiriesController extends Controller
{
    public function createold(Request $request)
    {
        /**
         * Get a validator for an incoming registration request.
         *
         * @param  array  $request
         * @return \Illuminate\Contracts\Validation\Validator
         */
        $user = auth()->guard('api')->user();
        $valid = validator($request->only('requisition_id', 'vendor_name', 'vendor_address', 'vendor_contact', 'vendor_email', 'price',
                                          'note', 'group_id'), [
            'requisition_id' => 'required',
            'vendor_name' => 'required', 
            'vendor_address' => 'required', 
            'vendor_contact' => 'required', 
            'vendor_email' => 'required',
            'price' => 'required',
            'note' => 'required',
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $data = request()->only('requisition_id', 'vendor_name', 'vendor_address', 'vendor_contact', 'vendor_email', 'price',
                                          'note', 'group_id');
        $group_id = null;
        if(isset($data['group_id']))
            $group_id = $data['group_id'];

        $requisition = Requisitions::where('id',$data['requisition_id'])->first();
        if($requisition->status==1) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'Requisition status is closed, you can not create enquiry on closed requisitions'
            ]);
        }

        $enquiry_exists = Enquiries::where('requisition_id',$data['requisition_id'])->where('user_id',$user->id)->exists();
        if($enquiry_exists) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'You have already created enquiry for this requisition'
            ]);
        }

        $enquiry = Enquiries::create([
            'requisition_id' => $data['requisition_id'],
            'vendor_name' => $data['vendor_name'], 
            'vendor_address' => $data['vendor_address'], 
            'vendor_contact' => $data['vendor_contact'], 
            'vendor_email' => $data['vendor_email'],
            'user_id' => $user->id,
            'price' => $data['price'],
            'note' => $data['note'],
            'group_id' => $group_id
        ]);
        
        $enquiry->reference_enquiry_no = 'PSENQ' . $enquiry->id;
        $enquiry->save();

        $points_master = PointMaster::where('slug','bidding')->first();
        if($points_master) {
            $wallet_points = $points_master->point;
            $user->wallet_points -= $wallet_points;
            $user->save();

            $message = "Substracted ".$wallet_points." points for bidding";
            $pointHistory = new PointHistory;
            $pointHistory->message = $message;
            $pointHistory->points = $wallet_points;
            $pointHistory->user_id = $user->id;
            $pointHistory->save();
        }
        
        $enquiry = Enquiries::with('requisition.requisition_items', 'requisition.user', 'user')->find($enquiry->id);
        
        if($enquiry) {
            return response()->json([
                'errorCode' => 0,
                'data' => $enquiry,
                'message' => 'Enquiry added successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Enquiry add failed'
        ]);
    }

    public function create(Request $request)
    {
        /**
         * Get a validator for an incoming registration request.
         *
         * @param  array  $request
         * @return \Illuminate\Contracts\Validation\Validator
         */
        $user = auth()->guard('api')->user();
        $valid = validator($request->only('requisition_id', 'price', 'note', 'group_id'), [
            'requisition_id' => 'required',
            'price' => 'required',
            'note' => 'required',
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $isWalletPoint = $this->canDoActivity('bidding');
        if(!$isWalletPoint) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'You dont have enough points in your wallet'
            ]);
        }

        $data = request()->only('requisition_id', 'price', 'note', 'group_id');
        $group_id = null;
        if(isset($data['group_id']))
            $group_id = $data['group_id'];

        $requisition = Requisitions::where('id',$data['requisition_id'])->first();
        if($requisition->status==1) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'Requisition status is closed, you can not create enquiry on closed requisitions'
            ]);
        }

        $enquiry_exists = Enquiries::where('requisition_id',$data['requisition_id'])->where('user_id',$user->id)->exists();
        if($enquiry_exists) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'You have already created enquiry for this requisition'
            ]);
        }

        $enquiry = Enquiries::create([
            'requisition_id' => $data['requisition_id'],
            'user_id' => $user->id,
            'price' => $data['price'],
            'note' => urldecode($data['note']),
            'group_id' => $group_id
        ]);
        
        $enquiry->reference_enquiry_no = 'PSENQ' . $enquiry->id;
        $enquiry->save();

        $points_master = PointMaster::where('slug','bidding')->first();
        if($points_master) {
            $wallet_points = $points_master->point;
            $user->wallet_points -= $wallet_points;
            $user->save();

            $message = "Substracted ".$wallet_points." points for bidding";
            $pointHistory = new PointHistory;
            $pointHistory->message = $message;
            $pointHistory->points = $wallet_points;
            $pointHistory->user_id = $user->id;
            $pointHistory->save();
        }
        
        $enquiry = Enquiries::with('requisition.requisition_items', 'requisition.user', 'user')->find($enquiry->id);
        
        if($enquiry) {
            return response()->json([
                'errorCode' => 0,
                'data' => $enquiry,
                'message' => 'Enquiry added successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Enquiry add failed'
        ]);
    }
	
	public function retrieve(Request $request, $id)
    {
		// $enquiry = Enquiries::with('requisition.requisition_items', 'requisition.user', 'group', 'group.group_user', 'group.group_user.user', 'user')->find($id);
		$enquiry = Enquiries::
            select('*',DB::raw('(SELECT avg(rating) FROM vendor_reviews WHERE vendor_reviews.vendor_id=enquiries.user_id) AS vendor_avg_rating'))
            ->with('requisition.user', 'group', 'group.group_user', 'group.group_user.user', 'user')->where('requisition_id',$id)->get();
		if(count($enquiry)) {
            $enquiry = $enquiry->map(function($value, $key) {
                $value->vendor_name = $value->user->name;
                $value->vendor_address = $value->user->address;
                $value->vendor_contact = $value->user->mobile;
                $value->vendor_email = $value->user->email;
                $value->vendor_avg_rating = ($value->vendor_avg_rating==null)?'0':number_format((float)$value->vendor_avg_rating, 1, '.', '');
                return $value;
            });

            return response()->json([
                'errorCode' => 0,
                'data' => $enquiry,
                'message' => 'Get enquiries successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get enquiries failed'
        ]);
    }
	
	public function updateold(Request $request, $id)
    {
        /**
         * Get a validator for an incoming registration request.
         *
         * @param  array  $request
         * @return \Illuminate\Contracts\Validation\Validator
         */
                $valid = validator($request->only('requisition_id', 'vendor_name', 'vendor_address', 'vendor_contact', 'vendor_email',
												  'price', 'note'), [
			'requisition_id' => 'required',
			'vendor_name' => 'required', 
			'vendor_address' => 'required', 
			'vendor_contact' => 'required', 
			'vendor_email' => 'required',
			'price' => 'required',
			'note' => 'required',
        ]);

        if ($valid->fails() || empty($id)) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $data = request()->only('requisition_id', 'vendor_name', 'vendor_address', 'vendor_contact', 'vendor_email', 'price', 'note');
		
        $requisition = Requisitions::where('id',$data['requisition_id'])->first();
        if($requisition->status==1) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'Requisition status is closed, you can not update enquiry on closed requisitions'
            ]);
        }

        Enquiries::where('id', $id)->update([
			'requisition_id' => $data['requisition_id'],
			'vendor_name' => $data['vendor_name'], 
			'vendor_address' => $data['vendor_address'], 
			'vendor_contact' => $data['vendor_contact'], 
			'vendor_email' => $data['vendor_email'],
			'user_id' => Auth::user()->id,
			'price' => $data['price'],
			'note' => $data['note']
        ]);
		
		$enquiry = Enquiries::with('requisition.requisition_items', 'requisition.user', 'user')->find($id);
		
		if($enquiry) {
            return response()->json([
                'errorCode' => 0,
                'data' => $enquiry,
                'message' => 'Enquiry updated successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Enquiry update failed'
        ]);			
    }

    public function update(Request $request, $id)
    {
        /**
         * Get a validator for an incoming registration request.
         *
         * @param  array  $request
         * @return \Illuminate\Contracts\Validation\Validator
         */
                $valid = validator($request->only('requisition_id', 'price', 'note'), [
            'requisition_id' => 'required',
            'price' => 'required',
            'note' => 'required',
        ]);

        if ($valid->fails() || empty($id)) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $data = request()->only('requisition_id', 'price', 'note');
        
        $requisition = Requisitions::where('id',$data['requisition_id'])->first();
        if($requisition->status==1) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'Requisition status is closed, you can not update enquiry on closed requisitions'
            ]);
        }

        Enquiries::where('id', $id)->update([
            'requisition_id' => $data['requisition_id'],
            'price' => $data['price'],
            'note' => urldecode($data['note'])
        ]);
        
        $enquiry = Enquiries::with('requisition.requisition_items', 'requisition.user', 'user')->find($id);
        
        if($enquiry) {
            return response()->json([
                'errorCode' => 0,
                'data' => $enquiry,
                'message' => 'Enquiry updated successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Enquiry update failed'
        ]);         
    }
	
	public function retrieveAll(Request $request)
    {
		$enquiries = Enquiries::with('requisition.requisition_items', 'requisition.user', 'user')->where('user_id', Auth::user()->id)->orderBy('id', 'desc')->get();
		
		if(count($enquiries)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $enquiries,
                'message' => 'Get enquiries successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get enquiries failed'
        ]);
    }
	
	public function retrieveByRequisitionId(Request $request, $requisitionsId)
    {
        $user = auth()->guard('api')->user();
		$enquiries = Enquiries::with('requisition.requisition_items', 'requisition.user', 'user')->where('requisition_id', $requisitionsId)->where('user_id', $user->id)->orderBy('id', 'desc')->get();
		if(count($enquiries)) {
            $enquiries = $enquiries->map(function($value, $key) {
                $value->vendor_name = $value->user->name;
                $value->vendor_address = $value->user->address;
                $value->vendor_contact = $value->user->mobile;
                $value->vendor_email = $value->user->email;
                
                return $value;
            });
            return response()->json([
                'errorCode' => 0,
                'data' => $enquiries,
                'message' => 'Get enquiries successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get enquiries failed'
        ]);
    }
	
	public function updateStatus(Request $request, $enquiryId)
	{
		/**
         * Get a validator for an incoming registration request.
         *
         * @param  array  $request
         * @return \Illuminate\Contracts\Validation\Validator
         */
		$valid = validator($request->only('status'), [
			'status' => 'integer|required'
        ]);

        if ($valid->fails() || empty($enquiryId)) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $data = request()->only('status');
		
		$enquiry = Enquiries::with('requisition.requisition_items', 'requisition.user', 'user')->find($enquiryId);
		
		$updateData = array();
		$updateData['status'] = $data['status'];
		//if($data['status'] == 1 && $enquiry->status == 0) {
		//	$updateData['bidder_commission'] = $enquiry->price * 0.03;
		//	$updateData['requisition_commission'] = $enquiry->price * 0.02;
		//}		
        Enquiries::where('id', $enquiryId)->update($updateData);
		if($data['status'] == 1 || $data['status'] == 2) {
			$reqStatus = 1;
		} else {
			$reqStatus = 0;
		}
		$requisition = Requisitions::where('id', $enquiry->requisition_id)->update(['status' => $reqStatus]);
		$enquiry->refresh();
		if($enquiry) {
            return response()->json([
                'errorCode' => 0,
                'data' => $enquiry,
                'message' => 'Enquiry status updated successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Enquiry status update failed'
        ]);	
	}
	
}
