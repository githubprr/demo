<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\User;
use App\Order;
use App\CategoryGroupMaster;
use App\CategoryMaster;
use App\SubcategoryMaster;
use App\ItemMaster;
use App\ShopItem;
use App\Blog;
use App\Group;
use App\ForumQuestion;
use App\QuizMaster;
use App\Ticket;
use DB;

class AdminController extends Controller
{
    public function index()
    {
    	$privileges = Auth::user()->privileges;
    	if($privileges==1) {
    		$famers_count = User::where('privileges',4)->count();
    		$companies_count = User::where('privileges',2)->count();
    		$vendors_count = User::where('privileges',3)->count();
            $users_count = User::count();
    		$verified_count = User::where('verified',1)->count();
    		$non_verified_count = $users_count-$verified_count;
    		$subscribed_count = User::where('is_subscribed',1)->count();
    		$non_subscribed_count = $companies_count+$vendors_count-$subscribed_count;
    		$latest_members = User::with('privilege')->orderBy('id','desc')->take(15)->get();
    		$latest_orders = Order::select('orders.id','orders.order_id','shop_items.name','orders.qty','orders.price','orders.status','orders.payment_processor','orders.payment_method','orders.created_at','users.name as user_name')
	            ->join('shop_items', 'orders.item_id', 'shop_items.id')
	            ->join('users', 'orders.user_id', 'users.id')
	            ->orderBy('orders.id', 'desc')
	            ->take(10)
	            ->get();
    		$latest_tickets = Ticket::with('user')->where('answer',null)->orWhere('answer','')->orderBy('id','desc')->take(10)->get();
    		$category_groups_count = CategoryGroupMaster::count();
    		$categories_count = CategoryMaster::count();
			$subcategories_count = SubcategoryMaster::count();
			$items_count = ItemMaster::count();
			$shop_items_count = ShopItem::count();
			$quizs_count = QuizMaster::count();
			$forum_questions_count = ForumQuestion::count();
			$blogs_count = Blog::count();
			$groups_count = Group::count();
    		$tickets_count = Ticket::count();
			
			$month_name_array = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
			$current_year_order_sale_array = array();
            for ($i=0; $i < 12; $i++) { 
                array_push($current_year_order_sale_array, 0);
            }
            $current_year_order_sale = Order::select(DB::raw("sum(price) as total"), DB::raw("MONTH(created_at) as month_day"))
                ->whereYear('created_at', date('Y'))
                ->groupBy(DB::raw("month_day"))
                ->orderBy('id','ASC')
                ->pluck('total', 'month_day');

            if(count($current_year_order_sale)) {
                foreach ($current_year_order_sale as $key => $value) {
                	$current_year_order_sale_array[$key-1] = $value;
                }
            }
            $current_year_order_sale_array = implode(',', $current_year_order_sale_array);
 			// $labels = $users->keys();
	        // $data = $users->values();
	        $top_selling_shop_items = DB::table('shop_items')
	            ->join('orders','shop_items.id','=','orders.item_id')
	            ->selectRaw('shop_items.id, shop_items.name, shop_items.image, count(orders.id) AS total')
	            ->groupBy('shop_items.id')
	            ->orderBy('total','desc')
	            ->take(5)
	            ->get();
            return view('dashboard.super_admin', compact('famers_count','companies_count','vendors_count','users_count','verified_count','non_verified_count','subscribed_count','non_subscribed_count','latest_members','latest_orders','latest_tickets','category_groups_count','categories_count','subcategories_count','items_count','shop_items_count','blogs_count','groups_count','tickets_count','forum_questions_count','quizs_count','current_year_order_sale_array','top_selling_shop_items'));
    	}
        else if($privileges==2)
        	return view('dashboard.company');
        else if($privileges==3)
        	return view('dashboard.vendor');
        else if($privileges==6 || $privileges==7 || $privileges==8)
            return view('admin.dashboard');
    }

    public function unauthorized_access()
    {
        return view('unauthorized_access');
    }

    public function secretLogin($id)
    {
        Auth::logout();
        $user = User::where('id',$id)->first();
        Auth::login($user);
        return redirect('/home');
    }
}