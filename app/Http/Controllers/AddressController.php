<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Address;
use Auth;

class AddressController extends Controller
{
    public function index(){
    	$user = auth()->guard('api')->user();
        $address = Address::where('user_id', $user->id)->get();
        if(sizeof($address)>0) {
            return response()->json([
                'errorCode' => 0,
                'data' => $address,
                'message' => 'Get address successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get address failed'
        ]);
    }
    public function save(Request $request){
        $user = auth()->guard('api')->user();
        $valid = validator($request->only('building','area','pincode','district','city'), [
            'building' => 'required',
            'area' => 'required',
            'pincode' => 'required',
            'district' => 'required',
            'city' => 'required'
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $address = new Address;
        $address->building = $request->building;
        $address->area = $request->area;
        $address->pincode = $request->pincode;
        $address->district = $request->district;
        $address->city = $request->city;
        if(isset($request->landmark))
            $address->landmark = $request->landmark;
        $address->is_default = 0;
        $address->user_id = $user->id;
        $address->save();

        return response()->json([
            'errorCode' => 0,
            'message' => 'Address saved successful'
        ]);
    }
    public function edit($id){
        $address = Address::Where('id',$id)->first();
        if($address) {
            return response()->json([
                'errorCode' => 0,
                'data' => $address,
                'message' => 'Address get successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Address get failed'
        ]);
    }
    public function update(Request $request){
        $valid = validator($request->only('id','building','area','pincode','district','city'), [
            'id' => 'required',
            'building' => 'required',
            'area' => 'required',
            'pincode' => 'required',
            'district' => 'required',
            'city' => 'required'
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $address = Address::where('id',$request->id)->first();
        if($address) {
            $address->building = $request->building;
            $address->area = $request->area;
            $address->pincode = $request->pincode;
            $address->district = $request->district;
            $address->city = $request->city;
            if(isset($request->landmark))
                $address->landmark = $request->landmark;
            $address->save();
        }

        return response()->json([
            'errorCode' => 0,
            'message' => 'Address updated successful'
        ]);
    }
    public function delete($id){
    	$address = Address::Where('id',$id)->first();
        if($address->delete()) {
            return response()->json([
                'errorCode' => 0,
                'message' => 'Address deleted successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Address delete failed'
        ]);
    }
}
