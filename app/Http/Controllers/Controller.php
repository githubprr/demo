<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\User;
use Auth;
use View;
use App\Subscription;
use App\SpecialAddon;
use App\PointMaster;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    protected $user_id;
    protected $user;
    protected $privilege_roles;
    protected $user_permissions;
    public function __construct() {
        $this->middleware(function($request, $next) {
            if(Auth::check()) {
                $this->user_id = Auth::user()->id;
                $this->user = User::where('id', $this->user_id)->first();
                $this->privilege_roles = $this->user->privilege->privilege_roles;
                $this->user_permissions = array();
                foreach ($this->privilege_roles as $key => $value) {
                    $this->user_permissions[$value->module->slug] = $value;
                }
            	View::share('user_permissions', $this->user_permissions);
            }
            return $next($request);
        });
    }

    public function getSubscriptionDetails($column, $vendor_id = null) {
        $mySubscription = Subscription::where('user_id',(isset($vendor_id))?$vendor_id:Auth::user()->id)->first();
        return (isset($mySubscription))?$mySubscription->$column:null;
    }

    public function getSubscriptionDetailsRow() {
        $mySubscription = Subscription::where('user_id',Auth::user()->id)->first();
        return (isset($mySubscription))?$mySubscription:null;
    }

    public function getSpecialAddonDetails($package, $vendor_id = null) {
        $mySpecialAddons = SpecialAddon::where('user_id',(isset($vendor_id))?$vendor_id:Auth::user()->id)->where('special_addon_package_id',$package)->sum('qty');
        return (isset($mySpecialAddons))?$mySpecialAddons:null;
    }

    public function getPermission($slug,$type) {
        if($this->user->privileges==1)
            return true;
        $user_permission = $this->user_permissions[$slug];
        return $user_permission->$type;
    }

    public function canDoActivity($slug) {
        $wallet = $this->user->wallet_points;
        $points_master = PointMaster::where('slug',$slug)->first();
        if($points_master && ($wallet>=$points_master->point)) {
            return true;
        }
        return false;
    }

    public function sendFCMNotification($user_ids,$title,$body,$id,$type)
    {
        logger()->info('Job '.$type.' start');
        logger()->info('ID='.$id);
        $firebaseToken = User::whereNotNull('fcm_token')->whereIn('id',$user_ids)->pluck('fcm_token')->all();
        //$firebaseToken = User::where('id',$id)->pluck('fcm_token');
        if(isset($firebaseToken)) {
            // $SERVER_API_KEY = env('FCM_SERVER_KEY');
            $SERVER_API_KEY = "AAAAGIhuUfA:APA91bFCF4CR0NNMpZlhOGsUxWR5pcPmVQEff0bNjRA2nEF4wzVp9ycZeBxwveaTSs2k2Utdk-VtRo-JA4FdyTyQtcbWj6W8fcwCWxeUte1eWwquG0rmNpPrZtvZ50NBisFWHnKAr4Oz";

            $data = [
                "registration_ids" => $firebaseToken,
                'data' => [
                    'id' => $id,
                    'type' => $type,
                    "title" => $title,
                    "body" => $body  
                ]
            ];
            $dataString = json_encode($data);

            $headers = [
                'Authorization: key=' . $SERVER_API_KEY,
                'Content-Type: application/json',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

            $response = curl_exec($ch);
            return true;
        }
        else
        {
            return false;
        }
        logger()->info('Job '.$type.' end');
    }
}
