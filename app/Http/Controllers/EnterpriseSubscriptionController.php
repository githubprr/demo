<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Razorpay\Api\Api;
use Session;
use Exception;
use Config;
use App\User;
use App\Subscription;
use App\SubscriptionHistory;
use App\SubscriptionPackage;
use App\VendorSubscriptionFeature;
use App\Coupon;
use Carbon\Carbon;

class EnterpriseSubscriptionController extends Controller
{
    public function index(){
        $subscriptions = Subscription::with('user')->where('package_id',8)->orderBy('id','desc')->get();
        return view('enterprise_subscription.list', compact('subscriptions'));
    }
    public function add(){
        $subscription_user_ids = Subscription::where('package_id',8)->pluck('user_id');
        $users = User::
            select('users.id','users.name','users.is_subscribed','subscription_packages.name AS package_name')
            ->leftJoin('subscriptions','subscriptions.user_id','users.id')
            ->leftJoin('subscription_packages','subscriptions.package_id','subscription_packages.id')
            ->where('users.id','<>',2)
            ->where('users.id','<>',3)
            ->where('privileges',2)
            ->whereNotIn('users.id',$subscription_user_ids)
            ->orderBy('users.name','asc')
            ->groupBy('users.id')
            ->get();
        return view('enterprise_subscription.add', compact('users'));
    }
    public function save(Request $request)
    {
        $state_account_nos = $request->state_account_nos;
        $district_allow = 1;
        $taluka_allow = 1;
        $employee_nos = $request->employee_nos;
        $reports = $request->reports;
        $email_support = 1;
        $chat_support = 1;
        $call_support = 1;
        $question_answer_nos = $request->question_answer_nos;

        $start_date = Carbon::now()->format('Y-m-d');
        $end_date = Carbon::now()->addMonths(12)->format('Y-m-d');
        
        $subscription_exists = Subscription::where('user_id',$request->user_id)->first();
        if($subscription_exists)
            $subscription = $subscription_exists;
        else
            $subscription = new Subscription;
        $subscription->user_id = $request->user_id;
        $subscription->package_id = 8;
        $subscription->subscription_amount = $request->amount;
        $subscription->amount = $request->amount;
        $subscription->subscription_id = $request->subscription_id;
        $subscription->payment_processor = "razorpay";
        $subscription->start_date = $start_date;
        $subscription->end_date = $end_date;
        $subscription->status = 1;
        $subscription->state_account_nos = $state_account_nos;
        $subscription->employee_nos = $employee_nos;
        $subscription->district_allow = $district_allow;
        $subscription->taluka_allow = $taluka_allow;
        $subscription->reports = $reports;
        $subscription->question_answer_nos = $question_answer_nos;
        $subscription->email_support = $email_support;
        $subscription->chat_support = $chat_support;
        $subscription->call_support = $call_support;
        $subscription->coupon_id = null;
        $subscription->save();

        $subscriptionHistoryPrevious = SubscriptionHistory::where('user_id',$request->user_id)->orderBy('id','desc')->first();
        if($subscriptionHistoryPrevious) {
            $subscriptionHistoryPrevious->end_date = $start_date;
            $subscriptionHistoryPrevious->save();
        }
        $subscriptionHistory = new SubscriptionHistory;
        $subscriptionHistory->user_id = $request->user_id;
        $subscriptionHistory->package_id = 8;
        $subscriptionHistory->subscription_amount = $request->amount;
        $subscriptionHistory->amount = $request->amount;
        $subscriptionHistory->old_plan_discount = null;
        $subscriptionHistory->subscription_id = $request->subscription_id;
        $subscriptionHistory->payment_processor = "razorpay";
        $subscriptionHistory->start_date = $start_date;
        $subscriptionHistory->end_date = $end_date;
        $subscriptionHistory->status = 1;
        $subscriptionHistory->state_account_nos = $state_account_nos;
        $subscriptionHistory->employee_nos = $employee_nos;
        $subscriptionHistory->district_allow = $district_allow;
        $subscriptionHistory->taluka_allow = $taluka_allow;
        $subscriptionHistory->reports = $reports;
        $subscriptionHistory->question_answer_nos = $question_answer_nos;
        $subscriptionHistory->email_support = $email_support;
        $subscriptionHistory->chat_support = $chat_support;
        $subscriptionHistory->call_support = $call_support;
        $subscriptionHistory->coupon_id = null;
        $subscriptionHistory->save();

        $user = User::where('id', $request->user_id)->first();
        $user->is_subscribed = 1;
        $user->save();

        return redirect()->to('/enterpriseSubscriptionList')->with('success','Enterprise subscription added successful');
    }
    public function edit($id){
        $subscription = Subscription::with('user')->where('user_id',$id)->first();
        return view('enterprise_subscription.edit', compact('subscription'));
    }
}
