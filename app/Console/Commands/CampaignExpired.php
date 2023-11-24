<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Models\AppliedCampaign;
use Carbon\Carbon;

class CampaignExpired extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'campaign must be auto expired after 30 days, if no action is performed.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $today = date('Y-m-d,H:i:s');
        $campaigns = Campaign::where('status', '1')->get();
        foreach ($campaigns as $key => $campaign) {
            $diff = now()->diffInDays(Carbon::parse($campaign->updated_at));
            if ($diff > 30 ) {
                $campExpired = campaign::find($campaign->id);
                $campExpired->status = '5';
                $campExpired->expired_on = $today;
                $campExpired->save();
            }
        }
    }

}
