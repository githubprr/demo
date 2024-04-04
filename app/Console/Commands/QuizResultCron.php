<?php
   
namespace App\Console\Commands;
   
use Illuminate\Console\Command;
use App\QuizMaster;
use App\QuizResult;
use App\QuizArea;
use App\StateMaster;
use App\DistrictMaster;
use App\TalukaMaster;
use App\Notification;
   
class QuizResultCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quiz_result:cron';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //\Log::info("Cron is working fine!");
     
        /*
           Write your database logic we bellow:
           Item::create(['name'=>'hello new']);
        */

        $today = date('Y-m-d');
        $quiz_master = QuizMaster::where('result_date',$today)->where('is_done',0)->get();
        if(sizeof($quiz_master)>0) {
            foreach ($quiz_master as $key => $quiz) {
                \Log::info("Cron Quiz id=".$quiz->id);
                $quiz_result = QuizResult::
                    where('quiz_master_id',$quiz->id)
                    ->where('percentage','>=',$quiz->eligible_at)
                    ->take($quiz->winner_nos)
                    ->orderBy('percentage','desc')
                    ->get();
                if(sizeof($quiz_result)>0) {
                    foreach ($quiz_result as $key => $result) {
                        QuizResult::where('id',$result->id)->update(['rank' => $key+1]);
                    }
                    $quizArea = QuizArea::where('quiz_master_id',$quiz->id)->get();
                    if(sizeof($quizArea)>0) {
                        foreach ($quizArea as $key => $area) {
                            $notification = new Notification;
                            $notification->notification = $quiz->name.' quiz result is declared';
                            $notification->notification_hn = $quiz->name_hn.' क्विज का रिजल्ट घोषित';
                            $notification->notification_mr = $quiz->name_mr.' प्रश्नमंजुषा निकाल जाहीर केला आहे';
                            if($quiz->quiz_level==1)
                                $notification->state_id = $area->state_id;
                            else if($quiz->quiz_level==2) {
                                $district = DistrictMaster::where('id',$area->district_id)->first();
                                $notification->state_id = $district->state_id;
                                $notification->district_id = $area->district_id;
                            }
                            else if($quiz->quiz_level==3) {
                                $taluka = TalukaMaster::where('id',$area->taluka_id)->first();
                                $district = DistrictMaster::where('id',$taluka->district_id)->first();
                                $notification->state_id = $district->state_id;
                                $notification->district_id = $taluka->district_id;
                                $notification->taluka_id = $area->taluka_id;
                            }
                            $notification->save();
                        }
                    }
                }
                QuizMaster::where('id',$quiz->id)->update(['is_done' => 1]);
            }
        }
    }
}