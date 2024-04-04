<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notification;
use App\User;

class NotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notification_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification_id)
    {
        $this->notification_id = $notification_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        logger()->info('Job notification start');
        $header = 'en';
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
        $notification = Notification::whereNotNull($column)->where('id',$this->notification_id)->select('id',$lang,'state_id','district_id','taluka_id')->first();
        if($notification) {
            $user_ids = array();
            if(isset($notification->taluka_id))
                $taluka_users = collect(User::where('state_id',$notification->state_id)->where('district_id',$notification->district_id)->where('taluka_id',$notification->taluka_id)->pluck('id'));
            if(isset($notification->district_id))
                $district_users = collect(User::where('state_id',$notification->state_id)->where('district_id',$notification->district_id)->pluck('id'));
            $state_users = collect(User::where('state_id',$notification->state_id)->pluck('id'));
            
            if(isset($notification->taluka_id))
                $taluka_district = $taluka_users->merge($district_users);
            else if(isset($notification->district_id))
                $taluka_district = $district_users;
            if(isset($notification->district_id))
                $user_ids = $taluka_district->merge($state_users);
            else
                $user_ids = $state_users;
            
            
            // logger()->info('user ids for notification');
            // logger()->info($user_ids);
            $unique_user_ids = array_unique($user_ids->toArray());
            // logger()->info('unique user ids for notification');
            // logger()->info($unique_user_ids);

            if (count($user_ids)) {
                $firebaseToken = User::whereNotNull('fcm_token')->whereIn('id',$unique_user_ids)->pluck('fcm_token')->all();
                // logger()->info('firebaseToken user ids for notification');
                // logger()->info($firebaseToken);
                //$firebaseToken = User::where('id',$id)->pluck('fcm_token');
                if(isset($firebaseToken)) {
                    // $SERVER_API_KEY = env('FCM_SERVER_KEY');
                    $SERVER_API_KEY = "AAAAGIhuUfA:APA91bFCF4CR0NNMpZlhOGsUxWR5pcPmVQEff0bNjRA2nEF4wzVp9ycZeBxwveaTSs2k2Utdk-VtRo-JA4FdyTyQtcbWj6W8fcwCWxeUte1eWwquG0rmNpPrZtvZ50NBisFWHnKAr4Oz";

                    $data = [
                        "registration_ids" => $firebaseToken,
                        'data' => [
                            'id' => $notification->id,
                            'type' => 'notification',
                            "title" => $notification->notification,
                            "body" => ''
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
                }
            }
        }
        else {
            logger()->info('notification not found '.$this->notification_id);
        }
        logger()->info('Job notification end');
    }
}
