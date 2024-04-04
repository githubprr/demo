<?php
   
namespace App\Console\Commands;
   
use Illuminate\Console\Command;
use App\Coupon;
use Carbon\Carbon;
   
class CouponActiveCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coupon_active:cron';
    
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

        $couponIds = Coupon::whereDate('updated_at', '<=', Carbon::yesterday())->where('status',2)->pluck('id');
        if($couponIds) {
            $coupon = Coupon::whereIn('id',$couponIds)->update(['status' => 0]);
        }
    }
}