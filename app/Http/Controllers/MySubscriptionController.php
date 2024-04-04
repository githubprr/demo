<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Subscription;

class MySubscriptionController extends Controller
{
    public function index(){
        $role_id = Auth::user()->privileges;
        if($role_id==3) {
            $mySubscription = Subscription::where('user_id',Auth::user()->id)->first();
            return view('my_subscription.vendor', compact('mySubscription'));
        }
        else if($role_id==2) {
            $mySubscription = Subscription::where('user_id',Auth::user()->id)->first();
            return view('my_subscription.company', compact('mySubscription'));
        }
    }
}
