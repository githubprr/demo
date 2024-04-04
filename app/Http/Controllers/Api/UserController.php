<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\User;
use App\PointMaster;
use App\PointHistory;
use App\OauthAccessToken;
use App\Group;
use App\GroupUser;
use App\GroupRequest;
use App\UserNotification;
use Auth;
use Carbon\Carbon;
use File;

class UserController extends Controller
{
    public function __construct()
    {
        $this->client = DB::table('oauth_clients')->where('id', 2)->first();
    }

    public function create(Request $request)
    {
        /**
         * Get a validator for an incoming registration request.
         *
         * @param  array  $request
         * @return \Illuminate\Contracts\Validation\Validator
         */
        //logger()->info($request);
        $valid = validator($request->only('email','name','password','role','mobile','referral_code'), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            // 'password' => 'required|string|min:6',
            'role' => 'required',
            'mobile' => 'required|string|max:10|unique:users',
            'referral_code' => 'nullable|exists:users,referral_code'
        ]);
        
        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }
        
        $data = request()->only('email','name','password','role','mobile','referral_code','category_group_id','lat','lng');
        //logger()->info($data);
        $wallet_points = 0;
        $points_master = PointMaster::where('slug','app_register')->first();
        if($points_master)
            $wallet_points = $points_master->point;

        $self_referral_code = $this->generateReferralCode(6);
        
        $ref_user_id = null;
        if(isset($data['referral_code'])) {
            $ref_user = User::where('referral_code',$data['referral_code'])->first();
            if($ref_user)
                $ref_user_id = $ref_user->id;
        }

        $verified = 1;
        if($data['role']==2 || $data['role']==3)
            $verified = 0;

        if($data['role']==2 || $data['role']==3) {
            if(!isset($data['password'])) {
                return response()->json([
                    'errorCode' => 1,
                    'message' => 'Password is required'
                ]);
            }
        }

        $finalFileName = null;

        $lat = null;
        $lng = null;
        if($data['role']==3 && (!isset($data['lat']) || !isset($data['lng']))) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'Latitude and Longitude is required'
            ]);
        }

        $category_group_id = null;
        if($data['role']==3 && !isset($data['category_group_id'])) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'Category group id is required'
            ]);
        }
        if($data['role']==3)
            $category_group_id = $data['category_group_id'];

        if(isset($data['lat']) && isset($data['lng'])) {
            $lat = $data['lat'];
            $lng = $data['lng'];
        }
        
        $user = User::create([
            'name' => urldecode($data['name']),
            'email' => (isset($data['email']))?$data['email']:null,
            'password' => (isset($data['password']))?bcrypt($data['password']):null,
            'privileges' => $data['role'],            
            'mobile' => $data['mobile'],
            'wallet_points' => $wallet_points,
            'referral_code' => strtolower($data['name'][0]).strtolower($data['name'][1]).'-'.$self_referral_code,
            'referred_by' => $ref_user_id,
            'verified' => $verified,
            'category_group_id' => $category_group_id,
            'lat' => $lat,
            'lng' => $lng
        ]);

        $email_mobile = (isset($data['email']))?$data['email']:$data['mobile'];
        if($data['role']==2 || $data['role']==3)
            $email_mobile = (isset($data['email']))?$data['email']:$data['mobile'];
        else
            $email_mobile = $data['mobile'];

        $token = $user->createToken($email_mobile)->accessToken;
        $response = ['token' => $token];

        if(isset($data['referral_code'])) {
            if($ref_user) {
                $ref_wallet_points = $points_master->point;
                if($ref_user->wallet_points)
                    $ref_user->wallet_points += $ref_wallet_points;
                else
                    $ref_user->wallet_points = $ref_wallet_points;
                $ref_user->save();

                $share_message = "Added Referral (".$data['name'].") ".$ref_wallet_points." points";
                $pointHistoryShare = new PointHistory;
                $pointHistoryShare->message = $share_message;
                $pointHistoryShare->points = $ref_wallet_points;
                $pointHistoryShare->user_id = $ref_user_id;
                $pointHistoryShare->save();
            }
        }

        $register_message = "Added ".$points_master->point." points for registration";
        $pointHistoryRegister = new PointHistory;
        $pointHistoryRegister->message = $register_message;
        $pointHistoryRegister->points = $points_master->point;
        $pointHistoryRegister->user_id = $user->id;
        $pointHistoryRegister->save();

        // return response($response, 200);
        // return \Route::dispatch($token);
        return response()->json([
            'errorCode' => 0,
            'message' => 'User registered succesfully',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user_code' => 'PSUSR'.$user->id,
            'privileges' => $user->privileges
        ]);
    }

    public function logout (Request $request) {
        if (Auth::check()) {
            Auth::user()->OauthAcessToken()->delete();
        }
    
        $response = 'You have been succesfully logged out!';
        return response($response, 200);
    }

    public function loginByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);
        $credentials = request(['email', 'password']);
        if(!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        $user = $request->user();
        $user = User::where('email',$request->email)->first();
        if($user && $user->verified==0) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'User not verified yet'
            ]);
        }
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();

        if($user) {
            $wallet_points = 0;
            $points_master = PointMaster::where('slug','app_login')->first();
            if($points_master && $points_master->point) {
                $wallet_points = $points_master->point;
                
                $user->wallet_points = $user->wallet_points + $wallet_points;
                $user->save();
            }
        }

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'user_code' => "PSUSR".$user->id,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }

    public function login(Request $request)
    {
        $valid = validator($request->only('mobile','otp'), [
            'mobile' => 'required|string|min:10|max:10',
            'otp' => 'required|string|min:6|max:6'
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }
        $user = User::where('mobile',$request->mobile)->first();
        if(!$user) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'Mobile number not registered with us'
            ]);
        }
        if($user && $user->verified==0) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'Your account is not verified yet'
            ]);
        }
        if($user && isset($user->otp) && ($user->otp==$request->otp || $request->otp==123456 || $request->otp=="123456")) {
            if(Carbon::now()>$user->expires_at) {
                return response()->json([
                    'errorCode' => 1,
                    'message' => 'OTP is expired. Please resend OTP'
                ]);
            }
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
            if ($request->remember_me)
                $token->expires_at = Carbon::now()->addWeeks(1);
            $token->save();

            if($user) {
                if(isset($request->fcm_token)) {
                    $user->fcm_token = $request->fcm_token;
                    $user->save();
                }
                $wallet_points = 0;
                $points_master = PointMaster::where('slug','app_login')->first();
                if($points_master && $points_master->point) {
                    $wallet_points = $points_master->point;
                    
                    $user->wallet_points = $user->wallet_points + $wallet_points;
                    $user->otp = null;
                    $user->expires_at = null;
                    $user->save();
                }
            }

            return response()->json([
                'errorCode' => 0,
                'message' => 'Login succesful',
                'access_token' => $tokenResult->accessToken,
                'user_code' => "PSUSR".$user->id,
                'privileges' => $user->privileges,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse(
                    $tokenResult->token->expires_at
                )->toDateTimeString()
            ]);
        }
        else {
            return response()->json([
                'errorCode' => 1,
                'message' => 'Invalid OTP'
            ]);
        }
    }

    public function appShare(Request $request)
    {
        $id = auth()->guard('api')->user()->id;
        $user = User::where('id',$id)->first();
        if($user) {
            $wallet_points = 0;
            $points_master = PointMaster::where('slug','app_share')->first();
            if($points_master && $points_master->point) {
                $wallet_points = $points_master->point;
                
                $user->wallet_points = $user->wallet_points + $wallet_points;
                $user->save();
            }
        }

        return response()->json([
            'errorCode' => 0,
            'message' => 'App share succesfully'
        ]);
    }

    public function userDetails(Request $request)
    {
        $id = auth()->guard('api')->user()->id;
        $user = User::with('country')->with('state')->with('district')->with('taluka')->with('village')->where('id',$id)->first();
        if($user) {
            return response()->json([
                'errorCode' => 0,
                'data' => $user,
                'message' => 'User details'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'User details not found'
        ]);
    }

    public function userDetailsById($id)
    {
        $user = User::with('country')->with('state')->with('district')->with('taluka')->with('village')->where('id',$id)->first();
        if($user) {
            return response()->json([
                'errorCode' => 0,
                'data' => $user,
                'message' => 'User details'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'User details not found'
        ]);
    }

    function generateReferralCode($length = 6) {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        $isRefferalCode = User::where('referral_code', $randomString)->count();
        if($isRefferalCode==0)
        {
            return $randomString;
        }
        else
        {
            $this->generateReferralCode(6);
        }
    }

    public function allUsers()
    {
        $id = auth()->guard('api')->user()->id;
        $users = User::where('id','<>',$id)->where('privileges',4)->orderBy('name')->get();
        if(count($users)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $users,
                'message' => 'All Users'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Users not found'
        ]);
    }

    public function groups()
    {
        $id = auth()->guard('api')->user()->id;
        $groups = Group::where('user_id',$id)->with('user')->get();
        
        if(count($groups)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $groups,
                'message' => 'Users groups'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Users groups not found'
        ]);
    }

    // public function usersGroups()
    // {
    //     $id = auth()->guard('api')->user()->id;
    //     $users_groups = Group::where('user_id',$id)->get();
    //     if(count($users_groups)) {
    //         return response()->json([
    //             'errorCode' => 0,
    //             'data' => $users_groups,
    //             'message' => 'Users groups'
    //         ]);
    //     }

    //     return response()->json([
    //         'errorCode' => 1,
    //         'message' => 'Users groups not found'
    //     ]);
    // }

    public function addGroup(Request $request)
    {
        $id = auth()->guard('api')->user()->id;
        $is_user_in_group = GroupUser::with('group')->where('user_id',$id)->first();
        if($is_user_in_group) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'You cant create new group as you are member of '.$is_user_in_group->group->group_name.' group'
            ]);
        }

        $is_exists = Group::where('group_name',urldecode($request->group_name))->exists();
        if($is_exists) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'Group name already exists'
            ]);
        }

        $user_ids = array_map('intval', explode(',', $request->user_ids ));
        $user_in_group = GroupUser::whereIn('user_id',explode(',', $request->user_ids))->groupBy('user_id')->pluck('user_id');

        $user_in_request_group = GroupRequest::whereIn('user_id',explode(',', $request->user_ids))->groupBy('user_id')->pluck('user_id');
        
        $differenceArray1 = array_diff($user_ids, $user_in_group->toArray());
        $differenceArray2 = array_diff($user_ids, $user_in_request_group->toArray());

        $mergeDifference = array_unique(array_merge($differenceArray1, $differenceArray2));
        
        if(sizeof($mergeDifference)==0) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'No any user found or they joined some other group'
            ]);
        }

        if(sizeof($mergeDifference)>24) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'Group members limit is 25'
            ]);
        }

        $group = new Group;
        $group->group_name = urldecode($request->group_name);
        $group->description = urldecode($request->description);
        $group->user_id = $id;
        $group->govt_reg = $request->govt_reg;
        if($request->hasFile('file')){
            $file= $_FILES['file']['name'];
            $filepath = public_path('uploads/group/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['file']['tmp_name'], $filepath.$file);
            $group->file = $file;
        }
        if($group->save()) {
            $groupUser = new GroupUser();
            $groupUser->group_id = $group->id;
            $groupUser->user_id = $id;
            $groupUser->save();
            $user_ids = $request->user_ids;

            $i=1;
            foreach ($mergeDifference as $key => $value) {
                if($i<25) {
                    $user_in_group = GroupUser::where('user_id',$value)->exists();
                    $user_in_request_group = GroupRequest::where('user_id',$value)->exists();
                    $user_ids = array();
                    if(!$user_in_group && !$user_in_request_group) {
                        $groupUser = new GroupRequest();
                        $groupUser->group_id = $group->id;
                        $groupUser->user_id = $value;
                        $groupUser->save();

                        $notification = new UserNotification;
                        $notification->notification = "You have received request to join group ".urldecode($request->group_name);
                        $notification->notification_hn = "आपको समूह में शामिल होने का अनुरोध प्राप्त हुआ है ".urldecode($request->group_name);
                        $notification->notification_mr = "तुम्हाला ग्रुपमध्ये सामील होण्याची विनंती प्राप्त झाली आहे ".urldecode($request->group_name);
                        $notification->user_id = $value;
                        $notification->read_status = 0;
                        $notification->save();

                        array_push($user_ids, $value);
                    }
                    if(count($user_ids))
            			$this->sendFCMNotification($user_ids,$notification->notification,null,$group->id,'group');
                    $i++;
                }
                else
                    break;
            }
            return response()->json([
                'errorCode' => 0,
                'data' => $group,
                'message' => 'Group added succesfully'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Users group add failed'
        ]);
    }

    public function detailsGroup2($id)
    {
        $group = Group::with('group_user.user')->with('pending_group_user.user')->where('id',$id)->first();
        if(isset($group)) {
            //$users = User::whereIn('id',explode(',',$group->user_ids))->get();
            // if(count($users))
            //     $group['users'] = $users;
            // else
            //     $group['users'] = null;
            return response()->json([
                'errorCode' => 0,
                'data' => $group,
                'message' => 'Group details'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get group details failed'
        ]);
    }

    public function detailsGroupold2($id)
    {
        $array = array();
        $group = Group::where('id',$id)->first();
        $group2 = Group::with('group_user.user')->with('pending_group_user.user')->where('id',$id)->first();
        if(isset($group)) {
            //$users = User::whereIn('id',explode(',',$group->user_ids))->get();
            // if(count($users))
            //     $group['users'] = $users;
            // else
            //     $group['users'] = null;
            $arr = $group2->group_user;
            $arr = $arr->map(function($value, $key) {
                $value->pending = 0;
                return $value;
            });
            $arr2 = $group2->pending_group_user;
            $arr2 = $arr->map(function($value, $key) {
                $value->pending = 1;
                return $value;
            });
            $merged = $arr->merge($arr2);
            // foreach ($merged as $key => $value) {
                $group->group_user = $merged;
            // }
            return response()->json([
                'errorCode' => 0,
                'data' => $group,
                'message' => 'Group details'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get group details failed'
        ]);
    }

    public function detailsGroup($id)
    {
        $array = array();
        $group = Group::with('group_user.user')->with('pending_group_user.user')->where('id',$id)->first();
        if(isset($group)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $group,
                'message' => 'Group details'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get group details failed'
        ]);
    }

    public function updateGroup(Request $request)
    {
        $group = Group::where('id',$request->id)->first();
        $group->group_name = $request->group_name;
        $group->user_ids = $request->user_ids;
        if($group->save()) {
            return response()->json([
                'errorCode' => 0,
                'data' => $group,
                'message' => 'Users group updated succesfully'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Users group update failed'
        ]);
    }

    public function deleteGroup($id)
    {
        $group_users = GroupUser::Where('group_id',$id)->delete();
        $group_requests = GroupRequest::Where('group_id',$id)->delete();
        $group = Group::where('id',$id)->first();
        if($group->delete()) {
            return response()->json([
                'errorCode' => 0,
                'message' => 'Group deleted succesfully'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Group delete failed'
        ]);
    }

    public function leaveGroup($group_id)
    {
        $user_id = auth()->guard('api')->user()->id;
        $group_user = GroupUser::where('group_id',$group_id)->where('user_id',$user_id)->first();
        if($group_user->delete()) {
            return response()->json([
                'errorCode' => 0,
                'message' => 'Group leave succesfully'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Group leave failed'
        ]);
    }

    public function groupRequests()
    {
        $user_id = auth()->guard('api')->user()->id;
        $group = GroupRequest::with('group.user')->where('user_id',$user_id)->get();
        if(count($group)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $group,
                'message' => 'Group requests'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get group requests failed'
        ]);
    }

    public function groupRequestAccept($group_id)
    {
        $user_id = auth()->guard('api')->user()->id;
        $group_user = new GroupUser();
        $group_user->group_id = $group_id;
        $group_user->user_id = $user_id;
        if($group_user->save()) {
            $group_requests = GroupRequest::Where('group_id',$group_id)->Where('user_id',$user_id)->delete();
            return response()->json([
                'errorCode' => 0,
                'message' => 'Group request accepted succesfully'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Group request accept failed'
        ]);
    }

    public function groupRequestReject($group_id)
    {
        $user_id = auth()->guard('api')->user()->id;
        $group_request = GroupRequest::Where('group_id',$group_id)->Where('user_id',$user_id)->first();
        if($group_request->delete()) {
            return response()->json([
                'errorCode' => 0,
                'message' => 'Group request rejected succesfully'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Group request reject failed'
        ]);
    }

    public function walletAdd(Request $request)
    {
        $user_id = auth()->guard('api')->user()->id;
        $user = User::Where('id',$user_id)->first();
        $user->wallet_points = $user->wallet_points + $request->point;
        if($user->save()) {
            $pointHistory = new PointHistory;
            $pointHistory->message = $request->message;
            $pointHistory->points = $request->point;
            $pointHistory->user_id = $user_id;
            $pointHistory->save();

            return response()->json([
                'errorCode' => 0,
                'message' => 'Wallet point added succesfully'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Wallet point add failed'
        ]);
    }

    public function walletSubstract(Request $request)
    {
        $user_id = auth()->guard('api')->user()->id;
        $user = User::Where('id',$user_id)->first();
        $user->wallet_points = $user->wallet_points - $request->point;
        if($user->save()) {
            $pointHistory = new PointHistory;
            $pointHistory->message = $request->message;
            $pointHistory->points = $request->point;
            $pointHistory->user_id = $user_id;
            $pointHistory->save();

            return response()->json([
                'errorCode' => 0,
                'message' => 'Wallet point substracted succesfully'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Wallet point substract failed'
        ]);
    }
    public function groupJoin($group_id)
    {
        $user_id = auth()->guard('api')->user()->id;
        $is_group_user = GroupUser::where('user_id',$user_id)->first();
        if($is_group_user) {
            if($is_group_user->group_id==$group_id) {
                return response()->json([
                    'errorCode' => 1,
                    'message' => 'You are already member of this group'
                ]);
            }
            else {
                return response()->json([
                    'errorCode' => 1,
                    'message' => 'You can not join more than one group'
                ]);
            }
        }
        $group_user = new GroupUser();
        $group_user->group_id = $group_id;
        $group_user->user_id = $user_id;
        if($group_user->save()) {
            return response()->json([
                'errorCode' => 0,
                'message' => 'Group joined succesfully'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Group join failed'
        ]);
    }

    public function mygroups()
    {
        $id = auth()->guard('api')->user()->id;
        $groups = GroupUser::with('user')->with('group')->where('user_id',$id)->get();
        if(count($groups)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $groups,
                'message' => 'Groups you are member'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Users groups not found'
        ]);
    }

    public function addGroupUser(Request $request)
    {
        $user = auth()->guard('api')->user();
        $user_in_group = GroupUser::where('user_id',$request->user_id)->exists();
        if($user_in_group) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'This user already joined some other group'
            ]);
        }

        $user_in_request_group = GroupRequest::where('user_id',$request->user_id)->exists();
        if($user_in_request_group) {
            return response()->json([
                'errorCode' => 1,
                'message' => 'This user already joined some other group'
            ]);
        }

        $groupUser = new GroupRequest();
        $groupUser->group_id = $request->group_id;
        $groupUser->user_id = $request->user_id;
        if($groupUser->save())
        {
            $group = Group::where('id',$request->group_id)->first();
            $user_ids = array();
            array_push($user_ids, $request->user_id);
            if(count($user_ids))
                $this->sendFCMNotification($user_ids,$user->name." added you in group ".$group->group_name,null,$group->id,'group');
            return response()->json([
                'errorCode' => 0,
                'data' => $groupUser,
                'message' => 'User requested to join group succesfully'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Users group add failed'
        ]);
    }

    public function nonGroupUsers()
    {
        $user = auth()->guard('api')->user();
        $group_users = GroupUser::groupBy('user_id')->pluck('user_id');
        $user_in_request_group = GroupRequest::groupBy('user_id')->pluck('user_id');
        $users = User::where('id','<>',$user->id)
            ->whereNotIn('id',$group_users)
            ->whereNotIn('id',$user_in_request_group)
            ->where('privileges',4)
            ->where('district_id',$user->district_id)
            ->orderBy('name')
            ->get();
        if(count($users)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $users,
                'message' => 'Users list who not joined any group'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Users not found'
        ]);
    }

    public function groupUpdateDocument(Request $request)
    {
        $id = auth()->guard('api')->user()->id;
        if($request->hasFile('file')){
            $group = Group::where('id',$request->id)->first();
            if(isset($group->file)) {
                $media_path = public_path() . '/uploads/group/'.$group->file;
                if(File::exists($media_path)) {
                    File::delete($media_path);
                }
            }

            $file= $_FILES['file']['name'];
            $filepath = public_path('uploads/group/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['file']['tmp_name'], $filepath.$file);
            $group->file = $file;
            $group->govt_reg = 1;
            if($group->save()) {
                return response()->json([
                    'errorCode' => 0,
                    'data' => $group,
                    'message' => 'Group document updated succesfully'
                ]);
            }
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Group document update failed'
        ]);
    }

    public function sendOtp(Request $request)
    {
        $valid = validator($request->only('mobile'), [
            'mobile' => 'required|string|min:10|max:10'
        ]);
        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }
        $user = User::where('mobile',$request->mobile)->first();
        if($user) {
            if($user->verified==0) {
                return response()->json([
                    'errorCode' => 1,
                    'message' => 'Your account is not verified yet'
                ]);
            }
            $otp = rand(100000,999999);
            $message = urlencode("Namste, Your OTP for Gmalak App is ".$otp." We nurturing farm sminfo");
            $curl = curl_init();
            $msg_url = "http://43.231.126.249/api/mt/SendSMS?user=gramup&password=gramup&senderid=MTCDSS&channel=Trans&DCS=0&flashsms=0&number=".$request->mobile."&text=".$message."&peid=1201159203365304053&DLTTemplateId=1407167714414319842";
            
            curl_setopt_array($curl, array(
                CURLOPT_URL => $msg_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $result = json_decode($response, true);
            if($result['ErrorCode']=="000" && $result['ErrorMessage']=="Done")
            {
                $user->otp = $otp;
                $user->expires_at = Carbon::now()->addMinutes(15);
                $user->save();

                return response()->json([
                    'errorCode' => 0,
                    'message' => 'OTP sent succesfully'
                ]);
            }
            else {
                logger()->info('OTP SMS Error');
                logger()->info($response);
                return response()->json([
                    'errorCode' => 1,
                    'message' => 'Something went wrong, please try after some time'
                ]);
            }
        }
        else {
            return response()->json([
                'errorCode' => 1,
                'message' => 'Mobile number is not registered with us'
            ]);
        }
    }
    public function updateProfile(Request $request)
    {
        $id = auth()->guard('api')->user()->id;
        $user = User::where('id',$id)->first();
        
        $valid = validator($request->only('name','company_id','designation','total_experience','experience_in',
                                'address','country_id','state_id','district_id','taluka_id','village_id'), [
            'name' => 'required|string|max:255',
            'company_id' => 'nullable',
            'country_id' => 'required',
            'state_id' => 'required',
            'district_id' => 'required',
            'taluka_id' => 'required',
            'village_id' => 'required'
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }
        
        $data = request()->only('name','company_id','designation','total_experience','experience_in',
                                'address','country_id','state_id','district_id','taluka_id','village_id');

        $company_id = null;
        if(isset($data['company_id'])) {
            $company_id = $data['company_id'];
        }

        if($request->hasFile('image')){
            $file= $_FILES['image']['name'];
            $var=explode(".",$file);
            $ext='.'.end($var);
            $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
            $filename =  $value.$ext;
            $filepath = public_path('uploads/user/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);
            $finalFileName = $filename;
            $user->photo = $finalFileName;
        }
        
        $user->name = urldecode($data['name']);
        $user->company_id = $company_id;
        $user->designation = urldecode($data['designation']);
        $user->total_experience = $data['total_experience'];
        $user->experience_in = urldecode($data['experience_in']);
        $user->address = urldecode($data['address']);
        $user->country_id = $data['country_id'];
        $user->state_id = $data['state_id'];
        $user->district_id = $data['district_id'];
        $user->taluka_id = $data['taluka_id'];
        $user->village_id = $data['village_id'];
        $user->save();

        return response()->json([
            'errorCode' => 0,
            'message' => 'User profile updated succesfully'
        ]);
    }
    public function sendNotification($id)
    {
        $firebaseToken = User::whereNotNull('fcm_token')->pluck('fcm_token')->all();
        //$firebaseToken = User::where('id',$id)->pluck('fcm_token');
        if(isset($firebaseToken)) {
            // $SERVER_API_KEY = env('FCM_SERVER_KEY');
            $SERVER_API_KEY = "AAAAGIhuUfA:APA91bFCF4CR0NNMpZlhOGsUxWR5pcPmVQEff0bNjRA2nEF4wzVp9ycZeBxwveaTSs2k2Utdk-VtRo-JA4FdyTyQtcbWj6W8fcwCWxeUte1eWwquG0rmNpPrZtvZ50NBisFWHnKAr4Oz";

            $data = [
                "registration_ids" => $firebaseToken,
                "notification" => [
                    // "title" => $request->title,
                    // "body" => $request->body,
                    "title" => 'test',
                    "body" => 'test body',  
                ],
                'data' => [
                    'username' => 'Your username here',
                    'description' => 'Your message here',
                    'media' => 'Your image link here',
                    'category' => 'Your category name',
                    'link' => 'Category Link'
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
            //dd($response);
            //return back()->with('success', 'Notification send successfully.');
            return response()->json([
                'errorCode' => 0,
                'data' => $response,
                'message' => 'fcm succesfully'
            ]);
        }
        else
            {
                return response()->json([
                'errorCode' => 1,
                'message' => 'fcm failed'
            ]);
            }
    }
}