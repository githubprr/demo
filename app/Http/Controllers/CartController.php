<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cart;
use Auth;
use stdClass;

class CartController extends Controller
{
    public function index(){
        $user = auth()->guard('api')->user();
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
        $cart = Cart::select('cart.id', 'shop_items.'.$lang,'cart.qty','shop_items.price','shop_items.discount','shop_items.grains','shop_items.image')
            ->join('shop_items', 'cart.item_id', 'shop_items.id')
            ->where('user_id', $user->id)
            ->get();
        if(sizeof($cart)>0) {
            $total = 0;
            $discount_total = 0;
            $grains_total = 0;
            foreach ($cart as $key => $item) {
                $total += $item->price * $item->qty;
                $discount_total += (($item->price * $item->qty) * $item->discount)/100;
                $grains_total += $item->grains;
            }
            $object = new stdClass;
            $object->items = $cart;
            $object->total = $total;
            $object->discount_total = $discount_total;
            $object->grains_total = $grains_total;
            return response()->json([
                'errorCode' => 0,
                'data' => $object,
                'message' => 'Get cart successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get cart failed'
        ]);
    }
    public function save(Request $request){
    	$user = auth()->guard('api')->user();
        $valid = validator($request->only('items'), [
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

        $data = request()->only('items');

        $cart_item_list = Cart::where('user_id', $user->id)->get();
        foreach ($data['items'] as $key => $item) {
            $cart_item_exists = $cart_item_list->where('item_id',$item['item_id'])->first();
            if(isset($cart_item_exists)) {
                $cart_item_exists->qty += $item['qty'];
                $cart_item_exists->save();
            }
            else {
                $cart_item = Cart::create([
                    'item_id' => $item['item_id'],
                    'qty' => $item['qty'],
                    'user_id' => $user->id
                ]);
            }
        }
        return response()->json([
            'errorCode' => 0,
            'message' => 'Item added to cart successful'
        ]);
    }
    public function delete($id){
    	$cart = Cart::Where('id',$id)->first();
        if($cart->delete()) {
            return response()->json([
                'errorCode' => 0,
                'message' => 'Cart item deleted successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Cart item delete failed'
        ]);
    }
}
