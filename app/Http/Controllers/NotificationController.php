<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notification;
use App\NotificationStatus;
use App\StateMaster;
use App\DistrictMaster;
use App\TalukaMaster;
use App\Jobs\NotificationJob;
use Auth;
use File;
use Validator;

class NotificationController extends Controller
{
    public function index(){
        if(!$this->getPermission('notifications','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $notifications = Notification::orderBy('id','desc')->get();
    	return view('notification.list', compact('notifications'));
    }
    public function add(){
        if(!$this->getPermission('notifications','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $states = StateMaster::all();
        return view('notification.add', compact('states'));
    }
    public function save(Request $request){
        if(!$this->getPermission('notifications','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'state_id' => 'required|integer'
            // 'district_id' => 'required|integer',
            // 'taluka_id' => 'required|integer'
        ], [
            'state_id.required' => 'State is required'
            // 'district_id.required' => 'District is required',
            // 'taluka_id.required' => 'Taluka is required'
        ]);
        if($validator->fails())
            return back()->with('validations',$validator->errors());

        $notification = new Notification;
        $notification->notification = $request->notification;
        $notification->notification_hn = $request->notification_hn;
        $notification->notification_mr = $request->notification_mr;
        $notification->state_id = $request->state_id;
        $notification->district_id = (isset($request->district_id))?$request->district_id:null;
        $notification->taluka_id = (isset($request->taluka_id))?$request->taluka_id:null;
        if($notification->save()) {
            dispatch(new NotificationJob($notification->id));
           return redirect('/notification')->with('success','Notification added successful');
        }
    	else
           return redirect('/notification')->with('error','Notification add failed');
    }
    public function edit($id){
        if(!$this->getPermission('notifications','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$notification = Notification::Where('id',$id)->first();
        if($notification) {
            $states = StateMaster::all();
            if(isset($notification->state_id))
                $districts = DistrictMaster::where('state_id',$notification->state_id)->get();
            else
                $districts = array();
            if(isset($notification->state_id))
                $talukas = TalukaMaster::where('district_id',$notification->district_id)->get();
            else
                $talukas = array();

            return view('notification.edit',compact('notification','states','districts','talukas'));
        }
        return redirect('/notification')->with('error','Notification not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('notifications','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'state_id' => 'required|integer'
            // 'district_id' => 'required|integer',
            // 'taluka_id' => 'required|integer'
        ], [
            'state_id.required' => 'State is required'
            // 'district_id.required' => 'District is required',
            // 'taluka_id.required' => 'Taluka is required'
        ]);
        if($validator->fails())
            return back()->with('validations',$validator->errors());

    	$notification = Notification::Where('id',$request->id)->first();
        if($notification) {
        	$notification->notification = $request->notification;
            $notification->notification_hn = $request->notification_hn;
            $notification->notification_mr = $request->notification_mr;
            $notification->state_id = $request->state_id;
            $notification->district_id = (isset($request->district_id))?$request->district_id:null;
            $notification->taluka_id = (isset($request->taluka_id))?$request->taluka_id:null;
            if($notification->save()) {
                dispatch(new NotificationJob($notification->id));
               return redirect('/notification')->with('success','Notification updated successful');
            }
        	else
               return redirect('/notification')->with('error','Notification update failed');
       }
        return redirect('/notification')->with('error','Notification not found');
    }
    public function delete($id){
    	if(!$this->getPermission('notifications','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$notification = Notification::Where('id',$id)->first();
        if($notification) {
        	if($notification->delete())
               return redirect('/notification')->with('success','Notification deleted successful');
        	else
               return redirect('/notification')->with('error','Notification delete failed');
       }
        return redirect('/notification')->with('error','Notification not found');
    }
    public function apiGetNotifications(){
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
        $taluka_notifications = collect(Notification::whereNotNull($column)->where('state_id',$user->state_id)->where('district_id',$user->district_id)->where('taluka_id',$user->taluka_id)->pluck('id'));
        $district_notifications = collect(Notification::whereNotNull($column)->where('state_id',$user->state_id)->where('district_id',$user->district_id)->whereNull('taluka_id')->pluck('id'));
        $state_notifications = collect(Notification::whereNotNull($column)->where('state_id',$user->state_id)->whereNull('district_id')->whereNull('taluka_id')->pluck('id'));
        
        $taluka_district = $taluka_notifications->merge($district_notifications);
        $notification_ids = $taluka_district->merge($state_notifications);
        
        $notifications = Notification::whereNotNull($column)
            ->leftJoin('notification_status', function($join) use ($user) {
                $join->on('notifications.id', '=', 'notification_status.notification_id')
                    ->where('notification_status.user_id',$user->id);
            })
            ->whereIn('notifications.id',$notification_ids)
            ->select('notifications.id',$lang,'state_id','district_id','taluka_id','notifications.created_at','notifications.updated_at','notification_status.notification_id as read_status')
            ->orderBy('id','desc')
            ->get();

        $notifications = $notifications->map(function($notification, $key) {
            $notification->read_status = (isset($notification->read_status))?1:0;
            return $notification;
        });
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
    public function apiGetNotificationsnew(){
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
        $state_id = $user->state_id;
        $district_id = $user->district_id;
        $taluka_id = $user->taluka_id;

        $notifications = Notification::select('id',$lang,'state_id','district_id','taluka_id','created_at','updated_at')
                ->whereNotNull($column)
                ->where('state_id',$user->state_id)
                ->where(function($query) use ($district_id,$taluka_id){
                    $query->where('taluka_id','=',$taluka_id)
                    ->orWhere(function($query) use ($district_id){
                        $query->where('district_id','=',$district_id)
                        ->whereNull('taluka_id');
                    });
                })
                ->where(function($query) use ($district_id,$state_id){
                    $query->where('district_id','=',$district_id)
                    ->orWhere(function($query) use ($state_id){
                        $query->where('state_id','=',$state_id)
                        ->whereNull('district_id')
                        ->whereNull('taluka_id');
                    });
                })->get();
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
    public function apiNotificationRead($id){
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

        $notification = Notification::whereNotNull($column)->where('id',$id)->select('id',$lang,'state_id','district_id','taluka_id','created_at','updated_at')->first();
        if($notification) {
            $notificationStatusExists = NotificationStatus::where('notification_id',$id)->where('user_id',$user->id)->exists();
            if(!$notificationStatusExists) {
                $notificationStatus = new NotificationStatus;
                $notificationStatus->notification_id = $id;
                $notificationStatus->user_id = $user->id;
                $notificationStatus->save();
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
