<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cart;
use App\Order;
use App\OrderHistory;
use App\ShopItem;
use Auth;
use stdClass;
use App\PointHistory;

class OrderController extends Controller
{
    // public function index(){
    //     $user = auth()->guard('api')->user();
    //     $header = request()->header('lang');
    //     $lang = 'name';
    //     $column = 'name';
    //     if($header=='hn') {
    //         $lang = 'name_hn as name';
    //         $column = 'name_hn';
    //     }
    //     else if($header=='mr') {
    //         $lang = 'name_mr as name';
    //         $column = 'name_mr';
    //     }
    //     $orders = Order::select('orders.id','orders.order_id','shop_items.'.$lang,'orders.qty','shop_items.price','shop_items.image','orders.status','user_address.building','user_address.area','user_address.landmark','user_address.pincode','user_address.district','user_address.city')
    //         ->join('shop_items', 'orders.item_id', 'shop_items.id')
    //         ->join('user_address', 'orders.user_address_id', 'user_address.id')
    //         ->where('orders.user_id', $user->id)
    //         ->orderBy('orders.id', 'desc')
    //         ->get();
    //     if(sizeof($orders)>0) {
    //         return response()->json([
    //             'errorCode' => 0,
    //             'data' => $orders,
    //             'message' => 'Get orders successful'
    //         ]);
    //     }

    //     return response()->json([
    //         'errorCode' => 1,
    //         'message' => 'Get orders failed'
    //     ]);
    // }
    public function apiIndex(){
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
        $orders = Order::select('orders.id','orders.order_id','shop_items.'.$lang,'orders.qty','orders.price','orders.discount','orders.grains','shop_items.image','orders.status','user_address.building','user_address.area','user_address.landmark','user_address.pincode','user_address.district','user_address.city','orders.created_at')
            ->join('shop_items', 'orders.item_id', 'shop_items.id')
            ->join('user_address', 'orders.user_address_id', 'user_address.id')
            ->where('orders.user_id', $user->id)
            ->orderBy('orders.id', 'desc')
            ->get();
        if(sizeof($orders)>0) {
            $orders = $orders->map(function($value, $key) {
                if($value->status==1)
                    $value->status = "Order Placed";
                else if($value->status==2)
                    $value->status = "Dispatch";
                else if($value->status==3)
                    $value->status = "Shipping";
                else if($value->status==4)
                    $value->status = "Transit";
                else if($value->status==5)
                    $value->status = "Delivered";
                return $value;
            });

            $total = 0;
            $discount_total = 0;
            $grains_total = 0;
            foreach ($orders as $key => $item) {
                $total += $item->price * $item->qty;
                $discount_total += (($item->price * $item->qty) * $item->discount)/100;
                $grains_total += $item->grains;
            }
            $object = new stdClass;
            $object->items = $orders;
            $object->total = $total;
            $object->discount_total = $discount_total;
            $object->grains_total = $grains_total;
            return response()->json([
                'errorCode' => 0,
                'data' => $object,
                'message' => 'Get orders successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get orders failed'
        ]);
    }
    public function apiSave(Request $request){
    	$user = auth()->guard('api')->user();
        $cart = Cart::Where('user_id',$user->id)->get();
        if(sizeof($cart)>0) {
            $valid = validator($request->only('user_address_id', 'payment_method'), [
                'user_address_id' => 'required',
                'payment_method' => 'required'
            ]);

            if ($valid->fails()) {
                $jsonError=response()->json($valid->errors()->all(), 400);
                return response()->json([
                    'errorCode' => 2,
                    'validation' => $jsonError->original,
                    'message' => 'Validation errors'
                ]);
            }
            $item_ids = $cart->pluck('item_id');
            $shop_items = ShopItem::WhereIn('id',$item_ids)->get();
            $payment_processor = (isset($request->payment_processor))?$request->payment_processor:null;
            $payment_id = (isset($request->payment_id))?$request->payment_id:null;
            foreach ($cart as $key => $item) {
                $shop_item = $shop_items->where('id',$item->item_id)->first();
                if($shop_item) {
                    $order = Order::create([
                        'item_id' => $item['item_id'],
                        'price' => $shop_item->price,
                        'qty' => $item['qty'],
                        'discount' => $shop_item->discount,
                        'grains' => $shop_item->grains,
                        'user_id' => $item['user_id'],
                        'status' => 1,
                        'user_address_id' => $request->user_address_id,
                        'payment_processor' => $payment_processor,
                        'payment_method' => $request->payment_method,
                        'payment_id' => $payment_id,
                        'is_refund' => 0
                    ]);
                    $order->order_id = 'GM'.$order->id;
                    if($order->save()) {
                        $orderHistory = new OrderHistory;
                        $orderHistory->order_id = $order->id;
                        $orderHistory->status = 1;
                        $orderHistory->save();

                        if(isset($shop_item->grains) && $shop_item->grains>0) {
                            $user->wallet_points -= $shop_item->grains;
                            $user->save();

                            $message = "Substracted ".$shop_item->grains." points for order place";
                            $pointHistoryShare = new PointHistory;
                            $pointHistoryShare->message = $message;
                            $pointHistoryShare->points = $shop_item->grains;
                            $pointHistoryShare->user_id = $user->id;
                            $pointHistoryShare->save();
                        }

                        $cart_delete = Cart::where('user_id',$user->id)->where('id',$item->id)->delete();
                    }
                }
            }
            $user_ids = array();
            array_push($user_ids, $user->id);
            $this->sendFCMNotification($user_ids,'Your order placed successfully',null,$order->id,'order');
            return response()->json([
                'errorCode' => 0,
                'message' => 'Order placed successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Order placed failed'
        ]);
    }
    public function apiDetails($id){
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
        $order = Order::select('orders.id','orders.order_id','shop_items.'.$lang,'orders.qty','orders.price','orders.discount','orders.grains','orders.status','orders.payment_processor','orders.payment_method','orders.payment_id','orders.is_refund','user_address.building','user_address.area','user_address.landmark','user_address.pincode','user_address.district','user_address.city','orders.created_at')
            ->join('shop_items', 'orders.item_id', 'shop_items.id')
            ->join('user_address', 'orders.user_address_id', 'user_address.id')
            ->where('orders.id', $id)
            ->first();
        if($order) {
            if($order->status==1)
                $order->status = "Order Placed";
            else if($order->status==2)
                $order->status = "Dispatch";
            else if($order->status==3)
                $order->status = "Shipping";
            else if($order->status==4)
                $order->status = "Transit";
            else if($order->status==5)
                $order->status = "Delivered";
            return response()->json([
                'errorCode' => 0,
                'data' => $order,
                'message' => 'Get order successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get order failed'
        ]);
    }
    public function apiCancel($id){
        $order = Order::Where('id',$id)->first();
        if($order) {
            $order->status = 'Cancelled';
            $order->is_refund = 1;
            $order->save();
            return response()->json([
                'errorCode' => 0,
                'message' => 'Order cancelled successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Order cancel failed'
        ]);
    }
    public function index(){
        if(!$this->getPermission('orders','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $orders = Order::select('orders.id','orders.order_id','shop_items.name','orders.qty','orders.price','orders.discount','orders.grains','shop_items.image','orders.status','orders.payment_processor','orders.payment_method','user_address.building','user_address.area','user_address.landmark','user_address.pincode','user_address.district','user_address.city','orders.created_at','users.name as user_name')
            ->join('shop_items', 'orders.item_id', 'shop_items.id')
            ->join('user_address', 'orders.user_address_id', 'user_address.id')
            ->join('users', 'orders.user_id', 'users.id')
            ->orderBy('orders.id', 'desc')
            ->get();
        return view('order.list', compact('orders'));
    }
    public function update(Request $request){
        if(!$this->getPermission('orders','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $order = Order::Where('id',$request->id)->first();
        if($order) {
            $order->status = $request->status;
            if($request->status>2 && isset($request->note))
                $order->note = $request->note;
            if($order->save()) {
                $orderHistory = new OrderHistory;
                $orderHistory->order_id = $request->id;
                $orderHistory->status = $request->status;
                $orderHistory->save();
                $user_ids = array();
                array_push($user_ids, $order->user_id);
                if($order->status==1)
                    $order_msg = "Your order placed successfully";
                else if($order->status==2)
                    $order_msg = "Your order is dispatched";
                else if($order->status==3)
                    $order_msg = "Your order is shipping";
                else if($order->status==4)
                    $order_msg = "Your order is in transit";
                else if($order->status==5)
                    $order_msg = "Your order is delivered";
                $this->sendFCMNotification($user_ids,$order_msg,$order->note,$order->id,'order');
               return back()->with('success','Order status updated successful');
            }
            else {
               return back()->with('error','Order status update failed');
            }
        }
        return redirect('/orders')->with('error','Order not found');
    }
    public function edit($id){
        if(!$this->getPermission('orders','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $order = Order::with('order_history')->where('id',$id)->first();
        if($order) {
            return view('order.edit', compact('order'));
        }
        return redirect('/orders')->with('error','Order not found');
    }
}
