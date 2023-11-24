<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Mail;
use App\Mail\IpWarningEmails;

class IpWarningEmail extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ip:warning-mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Record IP of Every account holders and flag IPs with more than two account access , shoot warning email to contact@prchitects.com';

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
        $users = User::whereNotNull('ip_address')->groupBy('ip_address')->get();
        if ($users->count() > 0) {
            foreach ($users as $key => $user) {
                $content = [
                    'users' => $users,
                ];

                if (env('IS_MAIL_ENABLE', true) == true) {
                    $recevier = Mail::to('contact@prchitects.com')->send(new IpWarningEmails($content));
                }
            }
        }
    }

}
