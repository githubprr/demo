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
use App\SpecialAddon;
use App\SubscriptionHistory;
use App\SpecialAddonPackage;
use App\VendorSubscriptionFeature;
use Carbon\Carbon;

class SpecialAddonController extends Controller
{
    public function index(){
        $subscription = Subscription::where('user_id',Auth::user()->id)->first();
        $specialAddons = SpecialAddon::where('user_id',Auth::user()->id)->orderBy('id','desc')->get();
        // if($subscription)
        //     return redirect()->to('my/subscription');
        $specialAddonPackages = SpecialAddonPackage::get();
        $rzp_key = Config::get('app.rzp_key');
        return view('special_addons.vendor_special_addons', compact('rzp_key','specialAddonPackages','specialAddons','subscription'));
    }
    public function save(Request $request)
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
                    $this->saveSpecialAddonData($input['id'], $input['amount'], $input['qty'], $response['id'], Auth::user()->id);
                }
            } catch (Exception $e) {
                return  $e->getMessage();
                // Session::put('error',$e->getMessage());
                return redirect()->to('/specialAddon/fail');
            }
        }          
        // Session::put('success', 'Payment successful');
        return redirect()->to('/specialAddon/success');
    }
    public function apiSaveSpecialAddon(Request $request)
    {
        $user = auth()->guard('api')->user();       
        $subscription = $this->saveSpecialAddonData($request->subscription_package_id, $request->amount, $request->qty, $request->payment_id, $user->id);
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
    public function saveSpecialAddonData($pkg_id, $amount, $qty, $payment_id, $user_id)
    {
        $specialAddon = new SpecialAddon;
        $specialAddon->user_id = $user_id;
        $specialAddon->special_addon_package_id = $pkg_id;
        $specialAddon->qty = $qty;
        $specialAddon->amount = $amount;
        $specialAddon->save();

        if($pkg_id==3 || $pkg_id==4) {
            $user = User::where('id', $user_id)->first();
            $user->verified_badge = 2;
            if($pkg_id==4)
                $user->trusted_badge = 2;
            $user->save();
        }

        return true;
    }
    public function success(){
        return view('special_addons.success');
    }
    public function fail(){
        return view('special_addons.fail');
    }
}
