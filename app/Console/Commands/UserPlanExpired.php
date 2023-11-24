<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscriptions;
use App\Models\User;
use Carbon\Carbon;
class UserPlanExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plan:expire';

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
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now();
        $expiredPlans = Subscriptions::select('id','subscription_plan_id','expire_at','user_id')->where('status', 1)->whereDate('expire_at', '<', $now->toDateString())->get();

        foreach ($expiredPlans as $key => $plan) {
           $updateStatus = Subscriptions::where('id',$plan->id)->update(['status'=>'3']);
           /*-----Switch to free plan----------------------------------*/
            createUserFreePlan($plan->user_id);

        }
    }
}
