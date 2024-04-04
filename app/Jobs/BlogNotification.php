<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Blog;
use App\User;

class BlogNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $blog_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($blog_id)
    {
        $this->blog_id = $blog_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        logger()->info('Job blog notification start');
        $header = 'en';
        $lang = 'title';
        $description_lang = 'description';
        $column = 'title';
        if($header=='hn') {
            $lang = 'title_hn as title';
            $description_lang = 'description_hn as description';
            $column = 'title_hn';
        }
        else if($header=='mr') {
            $lang = 'title_mr as title';
            $description_lang = 'description_mr as description';
            $column = 'title_mr';
        }
        $blog = Blog::whereNotNull($column)->where('id',$this->blog_id)->select('id',$lang,$description_lang,'media','media_type','state_id','district_id','taluka_id')->first();
        if($blog) {
            $user_ids = array();
            if(isset($blog->taluka_id))
                $taluka_users = collect(User::where('state_id',$blog->state_id)->where('district_id',$blog->district_id)->where('taluka_id',$blog->taluka_id)->pluck('id'));
            if(isset($blog->district_id))
                $district_users = collect(User::where('state_id',$blog->state_id)->where('district_id',$blog->district_id)->pluck('id'));
            $state_users = collect(User::where('state_id',$blog->state_id)->pluck('id'));
            
            if(isset($blog->taluka_id))
                $taluka_district = $taluka_users->merge($district_users);
            else if(isset($blog->district_id))
                $taluka_district = $district_users;
            if(isset($blog->district_id))
                $user_ids = $taluka_district->merge($state_users);
            else
                $user_ids = $state_users;
            
            
            // logger()->info('user ids for blog');
            // logger()->info($user_ids);
            $unique_user_ids = array_unique($user_ids->toArray());
            // logger()->info('unique user ids for blog');
            // logger()->info($unique_user_ids);

            if (count($user_ids)) {
                $firebaseToken = User::whereNotNull('fcm_token')->whereIn('id',$unique_user_ids)->pluck('fcm_token')->all();
                // logger()->info('firebaseToken user ids for blog');
                // logger()->info($firebaseToken);
                //$firebaseToken = User::where('id',$id)->pluck('fcm_token');
                if(isset($firebaseToken)) {
                    // $SERVER_API_KEY = env('FCM_SERVER_KEY');
                    $SERVER_API_KEY = "AAAAGIhuUfA:APA91bFCF4CR0NNMpZlhOGsUxWR5pcPmVQEff0bNjRA2nEF4wzVp9ycZeBxwveaTSs2k2Utdk-VtRo-JA4FdyTyQtcbWj6W8fcwCWxeUte1eWwquG0rmNpPrZtvZ50NBisFWHnKAr4Oz";

                    $data = [
                        "registration_ids" => $firebaseToken,
                        'data' => [
                            'id' => $blog->id,
                            'type' => 'blog',
                            "title" => $blog->title,
                            "body" => $blog->description
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
            logger()->info('blog not found '.$this->blog_id);
        }
        logger()->info('Job blog notification end');
    }
}
