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

class SubscriptionController extends Controller
{
    public function index(){
        $subscription = Subscription::with('coupon')->where('user_id',Auth::user()->id)->first();
        // if($subscription)
        //     return redirect()->to('my/subscription');
        $subscriptionPackages = SubscriptionPackage::where('role_id',Auth::user()->privileges)->get();
        $rzp_key = Config::get('app.rzp_key');
        $view = "subscriptions";
        if(isset($subscription))
    	   $view = "active_subscription";
        if(Auth::user()->privileges==2)
            return view('subscription.company_'.$view, compact('rzp_key','subscriptionPackages','subscription'));
        else if(Auth::user()->privileges==3)
            return view('subscription.vendor_'.$view, compact('rzp_key','subscriptionPackages','subscription'));
    }
    public function saveVendorSubscription(Request $request)
    {
        $input = $request->all();
        logger()->info('inputs=');
        logger()->info($input);
        $api = new Api(Config::get('app.rzp_key'), Config::get('app.rzp_secret'));
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
        if(count($input)  && !empty($input['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount'=>$payment['amount']));
                logger()->info('Razorpay=');
                logger()->info($response->toArray());
                if($response) {
                    // $vendorSubscriptionFeature = VendorSubscriptionFeature::where('vendor_subscription_pkg_id',$input['id'])->get();
                    // if($vendorSubscriptionFeature) {
                    //     $product_nos = $vendorSubscriptionFeature->where('slug','product_nos')->first();
                    //     $requisition_nos = $vendorSubscriptionFeature->where('slug','requisition_nos')->first();
                    //     $email_support = $vendorSubscriptionFeature->where('slug','email_support')->first();
                    //     $chat_support = $vendorSubscriptionFeature->where('slug','chat_support')->first();
                    //     $call_support = $vendorSubscriptionFeature->where('slug','call_support')->first();
                    //     $billing_software = $vendorSubscriptionFeature->where('slug','billing_software')->first();
                    //     $sms_alert = $vendorSubscriptionFeature->where('slug','sms_alert')->first();
                    //     $crm = $vendorSubscriptionFeature->where('slug','crm')->first();
                        
                        $this->saveVendorSubscriptionData($input['id'], $input['subscription_amount'], $input['amount'], 0, $input['coupon_id'], $response['id'], Auth::user()->id, 0);
                    // }
                }
            } catch (Exception $e) {
                return  $e->getMessage();
                // Session::put('error',$e->getMessage());
                return redirect()->to('/subscription/fail');
            }
        }          
        // Session::put('success', 'Payment successful');
        return redirect()->to('/subscription/success');
    }
    public function apiSaveVendorSubscription(Request $request)
    {
        $user = auth()->guard('api')->user();
        $coupon_id = (isset($request->coupon_id))?$request->coupon_id:0;
        $subscription = $this->saveVendorSubscriptionData($request->subscription_package_id, $request->subscription_amount, $request->amount, 0, $coupon_id, $request->payment_id, $user->id, 0);
        if($subscription) {
            return response()->json([
                'errorCode' => 0,
                'message' => 'Vendor subscription added successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Vendor subscription add failed'
        ]);
    }
    public function upgradeVendorSubscription(Request $request)
    {
        $input = $request->all();
        logger()->info('inputs=');
        logger()->info($input);
        $api = new Api(Config::get('app.rzp_key'), Config::get('app.rzp_secret'));
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
        if(count($input)  && !empty($input['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount'=>$payment['amount']));
                logger()->info('Razorpay=');
                logger()->info($response->toArray());
                if($response) {
                    // $vendorSubscriptionFeature = VendorSubscriptionFeature::where('vendor_subscription_pkg_id',$input['id'])->get();
                    // if($vendorSubscriptionFeature) {
                    //     $product_nos = $vendorSubscriptionFeature->where('slug','product_nos')->first();
                    //     $requisition_nos = $vendorSubscriptionFeature->where('slug','requisition_nos')->first();
                    //     $email_support = $vendorSubscriptionFeature->where('slug','email_support')->first();
                    //     $chat_support = $vendorSubscriptionFeature->where('slug','chat_support')->first();
                    //     $call_support = $vendorSubscriptionFeature->where('slug','call_support')->first();
                    //     $billing_software = $vendorSubscriptionFeature->where('slug','billing_software')->first();
                    //     $sms_alert = $vendorSubscriptionFeature->where('slug','sms_alert')->first();
                    //     $crm = $vendorSubscriptionFeature->where('slug','crm')->first();
                        
                        $this->saveVendorSubscriptionData($input['id'], $input['subscription_amount'], $input['amount'], $input['old_plan_discount'], $input['coupon_id'], $response['id'], Auth::user()->id, 1);
                    // }
                }
            } catch (Exception $e) {
                return  $e->getMessage();
                // Session::put('error',$e->getMessage());
                return redirect()->to('/subscription/fail');
            }
        }          
        // Session::put('success', 'Payment successful');
        return redirect()->to('/subscription/success');
    }
    public function saveVendorSubscriptionData($pkg_id, $subscription_amount, $amount, $old_plan_discount, $coupon_id, $payment_id, $user_id, $is_upgrade)
    {
        $product_nos = 0;
        $requisition_nos = 0;
        $email_support = 0;
        $chat_support = 0;
        $call_support = 0;
        $billing_software = 0;
        $sms_alert = 0;
        $crm = 0;
        $trusted_badge = 0;
        $verified_badge = 0;

        if($pkg_id==2) {
            $product_nos = 4;
            $requisition_nos = 10;
            $email_support = 1;
            $chat_support = 0;
            $call_support = 0;
            $billing_software = 0;
            $sms_alert = 0;
            $crm = 0;
        }
        else if($pkg_id==3) {
            $product_nos = 35;
            $requisition_nos = 100;
            $email_support = 1;
            $chat_support = 1;
            $call_support = 1;
            $billing_software = 0;
            $sms_alert = 0;
            $crm = 0;
        }
        else if($pkg_id==4) {
            $product_nos = -1;
            $requisition_nos = -1;
            $email_support = 1;
            $chat_support = 1;
            $call_support = 1;
            $billing_software = 1;
            $sms_alert = 1;
            $crm = 1;
            $trusted_badge = 2;
            $verified_badge = 2;
        }
        
        $start_date = Carbon::now()->format('Y-m-d');
        $end_date = Carbon::now()->addMonths(12)->format('Y-m-d');
        if($is_upgrade==0)
            $subscription = new Subscription;
        else
            $subscription = Subscription::where('user_id',$user_id)->first();
        $subscription->user_id = $user_id;
        $subscription->package_id = $pkg_id;
        $subscription->subscription_amount = $subscription_amount;
        $subscription->amount = $amount;
        $subscription->subscription_id = $payment_id;
        $subscription->payment_processor = "razorpay";
        $subscription->start_date = $start_date;
        $subscription->end_date = $end_date;
        $subscription->status = 1;
        $subscription->product_nos = $product_nos;
        $subscription->requisition_nos = $requisition_nos;
        $subscription->email_support = $email_support;
        $subscription->chat_support = $chat_support;
        $subscription->call_support = $call_support;
        $subscription->billing_software = $billing_software;
        $subscription->sms_alert = $sms_alert;
        $subscription->crm = $crm;
        $subscription->trusted_badge = $trusted_badge;
        $subscription->verified_badge = $verified_badge;
        $subscription->coupon_id = (isset($coupon_id) && $coupon_id>0)?$coupon_id:null;
        $subscription->save();

        $subscriptionHistoryPrevious = SubscriptionHistory::where('user_id',$user_id)->orderBy('id','desc')->first();
        if($subscriptionHistoryPrevious) {
            $subscriptionHistoryPrevious->end_date = $start_date;
            $subscriptionHistoryPrevious->save();
        }
        $subscriptionHistory = new SubscriptionHistory;
        $subscriptionHistory->user_id = $user_id;
        $subscriptionHistory->package_id = $pkg_id;
        $subscriptionHistory->subscription_amount = $subscription_amount;
        $subscriptionHistory->amount = $amount;
        $subscriptionHistory->old_plan_discount = (isset($old_plan_discount) && $old_plan_discount>0)?$old_plan_discount:null;
        $subscriptionHistory->subscription_id = $payment_id;
        $subscriptionHistory->payment_processor = "razorpay";
        $subscriptionHistory->start_date = $start_date;
        $subscriptionHistory->end_date = $end_date;
        $subscriptionHistory->status = 1;
        $subscriptionHistory->product_nos = $product_nos;
        $subscriptionHistory->requisition_nos = $requisition_nos;
        $subscriptionHistory->email_support = $email_support;
        $subscriptionHistory->chat_support = $chat_support;
        $subscriptionHistory->call_support = $call_support;
        $subscriptionHistory->billing_software = $billing_software;
        $subscriptionHistory->sms_alert = $sms_alert;
        $subscriptionHistory->crm = $crm;
        $subscription->trusted_badge = $trusted_badge;
        $subscription->verified_badge = $verified_badge;
        $subscriptionHistory->coupon_id = (isset($coupon_id) && $coupon_id>0)?$coupon_id:null;
        $subscriptionHistory->save();
        
        $user = User::where('id', $user_id)->first();
        $user->is_subscribed = 1;
        if($trusted_badge==1)
            $user->trusted_badge = $trusted_badge;
        if($verified_badge==1)
            $user->verified_badge = $verified_badge;
        $user->save();

        if(isset($coupon_id) && $coupon_id>0) {
            $coupon = Coupon::Where('id',$coupon_id)->first();
            if(isset($coupon) && $coupon->status==2 && $coupon->user_id==Auth::user()->id) {
                $coupon->status = 1;
                $coupon->save();
                if(Session::has('coupon_apply'))
                    Session::forget('coupon_apply');
            }
        }

        return true;
    }
    public function saveCompanySubscription(Request $request)
    {
        $input = $request->all();
        logger()->info('inputs=');
        logger()->info($input);
        $api = new Api(Config::get('app.rzp_key'), Config::get('app.rzp_secret'));
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
        if(count($input)  && !empty($input['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount'=>$payment['amount']));
                logger()->info('Razorpay=');
                logger()->info($response->toArray());
                if($response) {
                    // $vendorSubscriptionFeature = VendorSubscriptionFeature::where('vendor_subscription_pkg_id',$input['id'])->get();
                    // if($vendorSubscriptionFeature) {
                    //     $product_nos = $vendorSubscriptionFeature->where('slug','product_nos')->first();
                    //     $requisition_nos = $vendorSubscriptionFeature->where('slug','requisition_nos')->first();
                    //     $email_support = $vendorSubscriptionFeature->where('slug','email_support')->first();
                    //     $chat_support = $vendorSubscriptionFeature->where('slug','chat_support')->first();
                    //     $call_support = $vendorSubscriptionFeature->where('slug','call_support')->first();
                    //     $billing_software = $vendorSubscriptionFeature->where('slug','billing_software')->first();
                    //     $sms_alert = $vendorSubscriptionFeature->where('slug','sms_alert')->first();
                    //     $crm = $vendorSubscriptionFeature->where('slug','crm')->first();
                        
                        $this->saveCompanySubscriptionData($input['id'], $input['subscription_amount'], $input['amount'], 0, $input['coupon_id'], $response['id'], Auth::user()->id, 0);
                    // }
                }
            } catch (Exception $e) {
                return  $e->getMessage();
                // Session::put('error',$e->getMessage());
                return redirect()->to('/subscription/fail');
            }
        }          
        // Session::put('success', 'Payment successful');
        return redirect()->to('/subscription/success');
    }
    public function apiSaveCompanySubscription(Request $request)
    {
        $user = auth()->guard('api')->user();       
        $coupon_id = (isset($request->coupon_id))?$request->coupon_id:0;
        $subscription = $this->saveCompanySubscriptionData($request->subscription_package_id, $request->amount, 0, $coupon_id, $request->payment_id, $user->id, 0);
        if($subscription) {
            return response()->json([
                'errorCode' => 0,
                'message' => 'Company subscription added successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Company subscription add failed'
        ]);
    }
    public function upgradeCompanySubscription(Request $request)
    {
        $input = $request->all();
        logger()->info('inputs=');
        logger()->info($input);
        $api = new Api(Config::get('app.rzp_key'), Config::get('app.rzp_secret'));
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
        if(count($input)  && !empty($input['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount'=>$payment['amount']));
                logger()->info('Razorpay=');
                logger()->info($response->toArray());
                if($response) {
                    // $vendorSubscriptionFeature = VendorSubscriptionFeature::where('vendor_subscription_pkg_id',$input['id'])->get();
                    // if($vendorSubscriptionFeature) {
                    //     $product_nos = $vendorSubscriptionFeature->where('slug','product_nos')->first();
                    //     $requisition_nos = $vendorSubscriptionFeature->where('slug','requisition_nos')->first();
                    //     $email_support = $vendorSubscriptionFeature->where('slug','email_support')->first();
                    //     $chat_support = $vendorSubscriptionFeature->where('slug','chat_support')->first();
                    //     $call_support = $vendorSubscriptionFeature->where('slug','call_support')->first();
                    //     $billing_software = $vendorSubscriptionFeature->where('slug','billing_software')->first();
                    //     $sms_alert = $vendorSubscriptionFeature->where('slug','sms_alert')->first();
                    //     $crm = $vendorSubscriptionFeature->where('slug','crm')->first();
                        
                        $this->saveCompanySubscriptionData($input['id'], $input['subscription_amount'], $input['amount'], $input['old_plan_discount'], $input['coupon_id'], $response['id'], Auth::user()->id, 1);
                    // }
                }
            } catch (Exception $e) {
                return  $e->getMessage();
                // Session::put('error',$e->getMessage());
                return redirect()->to('/subscription/fail');
            }
        }          
        // Session::put('success', 'Payment successful');
        return redirect()->to('/subscription/success');
    }
    public function saveCompanySubscriptionData($pkg_id, $subscription_amount, $amount, $old_plan_discount, $coupon_id, $payment_id, $user_id, $is_upgrade)
    {
        $state_account_nos = 0;
        $district_allow = 0;
        $taluka_allow = 0;
        $employee_nos = 0;
        $reports = 0;
        $email_support = 0;
        $chat_support = 0;
        $call_support = 0;
        $question_answer_nos = 0;

        if($pkg_id==6) {
            $state_account_nos = 1;
            $employee_nos = 5;
            $district_allow = 1;
            $taluka_allow = 0;
            $reports = 12;
            $email_support = 1;
            $chat_support = 0;
            $call_support = 0;
            $question_answer_nos = 100;
        }
        else if($pkg_id==7) {
            $state_account_nos = 1;
            $employee_nos = 30;
            $district_allow = 1;
            $taluka_allow = 0;
            $reports = 48;
            $email_support = 1;
            $chat_support = 1;
            $call_support = 1;
            $question_answer_nos = 700;
        }
        
        $start_date = Carbon::now()->format('Y-m-d');
        $end_date = Carbon::now()->addMonths(12)->format('Y-m-d');
        $subscription = new Subscription;
        $subscription->user_id = Auth::user()->id;
        $subscription->package_id = $pkg_id;
        $subscription->subscription_amount = $subscription_amount;
        $subscription->amount = $amount;
        $subscription->subscription_id = $payment_id;
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
        $subscription->coupon_id = (isset($coupon_id) && $coupon_id>0)?$coupon_id:null;
        $subscription->save();

        $subscriptionHistoryPrevious = SubscriptionHistory::where('user_id',$user_id)->orderBy('id','desc')->first();
        if($subscriptionHistoryPrevious) {
            $subscriptionHistoryPrevious->end_date = $start_date;
            $subscriptionHistoryPrevious->save();
        }
        $subscriptionHistory = new SubscriptionHistory;
        $subscriptionHistory->user_id = $user_id;
        $subscriptionHistory->package_id = $pkg_id;
        $subscriptionHistory->subscription_amount = $subscription_amount;
        $subscriptionHistory->amount = $amount;
        $subscriptionHistory->old_plan_discount = (isset($old_plan_discount) && $old_plan_discount>0)?$old_plan_discount:null;
        $subscriptionHistory->subscription_id = $payment_id;
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
        $subscriptionHistory->coupon_id = (isset($coupon_id) && $coupon_id>0)?$coupon_id:null;
        $subscriptionHistory->save();

        $user = User::where('id', $user_id)->first();
        $user->is_subscribed = 1;
        $user->save();

        if(isset($coupon_id) && $coupon_id>0) {
            $coupon = Coupon::Where('id',$coupon_id)->first();
            if(isset($coupon) && $coupon->status==2 && $coupon->user_id==Auth::user()->id) {
                $coupon->status = 1;
                $coupon->save();
                if(Session::has('coupon_apply'))
                    Session::forget('coupon_apply');
            }
        }

        return true;
    }
    public function success(){
        return view('subscription.success');
    }
    public function fail(){
        return view('subscription.fail');
    }
    public function saveFreeSubscription(Request $request)
    {
        $user_id = Auth::user()->id;
        $pkg_id = $request->id;
        $this->saveFreeSubscriptionData($pkg_id, $user_id);
        return redirect()->to('/subscription/success');
    }
    public function apiSaveFreeSubscription(Request $request)
    {
        $user = auth()->guard('api')->user();       
        $pkg_id = $request->subscription_package_id;
        $subscription = $this->saveFreeSubscriptionData($pkg_id, $user->id);
        if($subscription) {
            return response()->json([
                'errorCode' => 0,
                'message' => 'Subscription added successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Subscription add failed'
        ]);
    }
    public function saveFreeSubscriptionData($pkg_id, $user_id)
    {
        $start_date = Carbon::now()->format('Y-m-d');
        $end_date = Carbon::now()->addMonths(12)->format('Y-m-d');
        $subscription = new Subscription;
        $subscriptionHistory = new SubscriptionHistory;
            
        if($pkg_id==1) {
            $product_nos = 1;
            $requisition_nos = 2;
            $email_support = 0;
            $chat_support = 0;
            $call_support = 0;
            $billing_software = 0;
            $sms_alert = 0;
            $crm = 0;

            $subscription->product_nos = $product_nos;
            $subscription->requisition_nos = $requisition_nos;
            $subscription->billing_software = $billing_software;
            $subscription->sms_alert = $sms_alert;
            $subscription->crm = $crm;

            $subscriptionHistory->product_nos = $product_nos;
            $subscriptionHistory->requisition_nos = $requisition_nos;
            $subscriptionHistory->billing_software = $billing_software;
            $subscriptionHistory->sms_alert = $sms_alert;
            $subscriptionHistory->crm = $crm;
        }
        else if($pkg_id==5) {
            $state_account_nos = 1;
            $district_allow = 0;
            $taluka_allow = 0;
            $employee_nos = 1;
            $reports = 0;
            $email_support = 0;
            $chat_support = 0;
            $call_support = 0;
            $question_answer_nos = 50;

            $subscription->state_account_nos = $state_account_nos;
            $subscription->employee_nos = $employee_nos;
            $subscription->district_allow = $district_allow;
            $subscription->taluka_allow = $taluka_allow;
            $subscription->reports = $reports;
            $subscription->question_answer_nos = $question_answer_nos;

            $subscriptionHistory->state_account_nos = $state_account_nos;
            $subscriptionHistory->employee_nos = $employee_nos;
            $subscriptionHistory->district_allow = $district_allow;
            $subscriptionHistory->taluka_allow = $taluka_allow;
            $subscriptionHistory->reports = $reports;
            $subscriptionHistory->question_answer_nos = $question_answer_nos;
        }
        $subscription->user_id = Auth::user()->id;
        $subscription->amount = 0;
        $subscription->subscription_amount = 0;
        $subscription->package_id = $pkg_id;
        // $subscription->subscription_id = $response['id'];
        // $subscription->payment_processor = "";
        $subscription->start_date = $start_date;
        $subscription->end_date = $end_date;
        $subscription->status = 1;
        $subscription->email_support = $email_support;
        $subscription->chat_support = $chat_support;
        $subscription->call_support = $call_support;
        $subscription->save();

        $subscriptionHistory->user_id = $user_id;
        $subscriptionHistory->package_id = $pkg_id;
        $subscriptionHistory->subscription_amount = 0;
        $subscriptionHistory->amount = 0;
        $subscriptionHistory->old_plan_discount = null;
        // $subscriptionHistory->subscription_id = $payment_id;
        // $subscriptionHistory->payment_processor = "razorpay";
        $subscriptionHistory->start_date = $start_date;
        $subscriptionHistory->end_date = $end_date;
        $subscriptionHistory->status = 1;
        $subscriptionHistory->email_support = $email_support;
        $subscriptionHistory->chat_support = $chat_support;
        $subscriptionHistory->call_support = $call_support;
        $subscriptionHistory->save();

        $user = User::where('id', Auth::user()->id)->first();
        $user->is_subscribed = 1;
        $user->save();

        return true;
    }
}
