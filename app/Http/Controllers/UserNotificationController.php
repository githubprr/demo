<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserNotification;
use App\User;
use App\Privilege;
use Auth;
use File;
use Validator;

class UserNotificationController extends Controller
{
    public function index(){
        if(!$this->getPermission('user_notifications','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $notifications = UserNotification::orderBy('id','desc')->get();
    	return view('user_notification.list', compact('notifications'));
    }
    // public function add(){
    //     $users = User::select('id','name','privileges')->orderBy('privileges','asc')->orderBy('name','asc')->get();
    //     $user_groups = $users->groupBy('privileges');
    //     $privileges = Privilege::pluck('name','id',);
    //     return view('user_notification.add', compact('user_groups','privileges'));
    // }
    public function add(){
        if(!$this->getPermission('user_notifications','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $users = User::select('id','name')->orderBy('name','asc')->get();
        // $user_groups = $users->groupBy('privileges');
        $privileges = Privilege::pluck('name','id');
        return view('user_notification.add', compact('users','privileges'));
    }
    public function save(Request $request){
        if(!$this->getPermission('user_notifications','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'users' => 'required'
        ], [
            'users.required' => 'User is required'
        ]);
        if($validator->fails())
            return back()->with('validations',$validator->errors());
        foreach($request->users as $user) {
            $user_ids = array();
            $notification = new UserNotification;
            $notification->notification = $request->notification;
            $notification->notification_hn = $request->notification_hn;
            $notification->notification_mr = $request->notification_mr;
            $notification->user_id = $user;
            $notification->read_status = 0;
            $notification->save();
            array_push($user_ids, $user);
            $this->sendFCMNotification($user_ids,$request->notification,null,$notification->id,'user_notification');
        }
        return redirect('/userNotification')->with('success','User Notification added successful');
    }
    public function edit($id){
        if(!$this->getPermission('user_notifications','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$notification = UserNotification::Where('id',$id)->first();
        if($notification) {
            $users = User::where('privileges',4)->orderBy('name','asc')->get();
            return view('user_notification.edit',compact('notification','users'));
        }
        return redirect('/userNotification')->with('error','User Notification not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('user_notifications','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer'
        ], [
            'user_id.required' => 'User is required'
        ]);
        if($validator->fails())
            return back()->with('validations',$validator->errors());

    	$notification = UserNotification::Where('id',$request->id)->first();
        if($notification) {
            $user_ids = array();
        	$notification->notification = $request->notification;
            $notification->notification_hn = $request->notification_hn;
            $notification->notification_mr = $request->notification_mr;
            $notification->user_id = $request->user_id;
            array_push($user_ids, $request->user_id);
            if($notification->save()) {
                $this->sendFCMNotification($user_ids,$request->notification,null,$request->id,'user_notification');
               return redirect('/userNotification')->with('success','User Notification updated successful');
        	}
            else
               return redirect('/userNotification')->with('error','User Notification update failed');
        }
        return redirect('/userNotification')->with('error','User Notification not found');
    }
    public function delete($id){
    	if(!$this->getPermission('user_notifications','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$notification = UserNotification::Where('id',$id)->first();
        if($notification) {
        	if($notification->delete())
               return redirect('/userNotification')->with('success','User Notification deleted successful');
        	else
               return redirect('/userNotification')->with('error','User Notification delete failed');
        }
        return redirect('/userNotification')->with('error','User Notification not found');
    }
    public function apiGetUserNotifications(){
        $user = auth()->guard('api')->user();
        $header = request()->header('lang');
        $lang = 'notification';
        $column = 'notification';
        if($header=='hn') {
            $lang = 'notification_hn as notification';
            $column = 'notification_hn';
        }
        else if($header=='mr') {
            $lang = 'notification_mr as notification';
            $column = 'notification_mr';
        }
        $notifications = UserNotification::whereNotNull($column)->where('user_id',$user->id)->select('id',$lang,'created_at','updated_at')->orderBy('id','desc')->get();
        if($notifications) {
            return response()->json([
                'errorCode' => 0,
                'data' => $notifications,
                'message' => 'Get notifications successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get notifications failed'
        ]);
    }
    public function apiUserNotificationRead($id){
        $user = auth()->guard('api')->user();
        $header = request()->header('lang');
        $lang = 'notification';
        $column = 'notification';
        if($header=='hn') {
            $lang = 'notification_hn as notification';
            $column = 'notification_hn';
        }
        else if($header=='mr') {
            $lang = 'notification_mr as notification';
            $column = 'notification_mr';
        }

        $notification = UserNotification::whereNotNull($column)->where('id',$id)->select('id',$lang,'created_at','updated_at')->first();
        if($notification) {
            if($notification->read_status==0) {
                $notification->read_status = 1;
                $notification->save();
            }
            return response()->json([
                'errorCode' => 0,
                'data' => $notification,
                'message' => 'Get notification successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get notification failed'
        ]);
    }
}
