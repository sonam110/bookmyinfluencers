<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Subscriptions;
class UpdateTopUpCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:topup-credits';

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
        $users = User::select('id','credit_balance','last_topup_date')->where('status', 1)->whereNotNull('last_topup_date')->whereDate('last_topup_date', '<=', $now->toDateString())->get();
        if(count($users) >0){
            echo Carbon::now() . '------credit update Start------' . PHP_EOL;
            foreach ($users as $key => $user) {
               $userPlan = Subscriptions::where('user_id',$user->id)->with('plan')->first();
               if(!empty($userPlan)){
                    if($userPlan->subscription_plan_id!='1'){
                        if(@$userPlan->plan->credit_rollover =='1'){
                            $credit_balance = $user->credit_balance + $userPlan->plan->credits;
                        } else{
                            $credit_balance  = $userPlan->plan->credits;
                        }
                        $updateCredit = User::find($user->id);
                        $updateCredit->credit_balance =  $credit_balance;
                        $updateCredit->last_topup_date =  date('Y-m-d H:i:s');
                        $updateCredit->save();

                        echo Carbon::now() . '----' . $updateCredit->id . '--user:ID----.' . PHP_EOL;

                        $transaction_id = \Str::random(10);
                        transactionHistory($transaction_id, $user->id, '2', 'Monthly credits '.$userPlan->plan->credits.' renew ', '1', $user->credit_balance, $userPlan->plan->credits, $updateCredit->credit_balance, '1', '', $user->id, 'users', $user->id,$user->currency,NULL);
                    }
               }
               
            }
            echo Carbon::now() . '------Sync Job End------' . PHP_EOL;
        }
    }
}
