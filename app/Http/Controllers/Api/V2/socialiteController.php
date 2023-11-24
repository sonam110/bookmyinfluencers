<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Channel;
use App\Http\Controllers\Api\V2\UserController;

class socialiteController extends Controller {

    /**
     * Redirect the user to the facebook authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($provider) {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderRedirect($RedirectData) {
        return $RedirectData;
    }

    /**
     * Obtain the user information from facebook.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback($provider) {
        $baseRedirURL = 'https://stage.bookmyinfluencers.com/social/login/';
        $ExistingChannelBelongs = '';
        switch ($provider) {
            case 'google':
                $db_column = 'google_id';
                break;
            default :
                $db_column = null;
        }
        try {
            $sUser = Socialite::driver($provider)->stateless()->user();
            $finduser = User::where($db_column, $sUser->id)->where('email', $sUser->email)->first();
            $UserExists = User::where('email', $sUser->email)->first();
            if (empty($finduser) && empty($UserExists)) {
                $finduser = User::create([
                            'email' => $sUser->email,
                            $db_column => $sUser->id,
                            'password' => encrypt(random_bytes(10)),
                            'is_google' => 1,
                            'account_type' => 1,
                            'status' => 0
                ]);
            } elseif (!empty($UserExists) && empty($finduser)) {
                if($UserExists->account_type =='0'){
                    User::where('email', $sUser->email)->update([$db_column => $sUser->id, 'is_google' => 1, 'account_type' => 1, 'status' => 0,'userType'=>'']);
                     UserController::attachAllChannelBelongsVerifiedEmail($UserExists->email, $UserExists->id);
                } else{
                    User::where('email', $sUser->email)->update([$db_column => $sUser->id, 'is_google' => 1, 'account_type' => 1]);
                }
                $finduser = User::where($db_column, $sUser->id)->where('email', $sUser->email)->first();
            }
            $finduser['access_token'] = $finduser->createToken('authToken')->accessToken;
            $finduser['reg_status'] = $finduser->status;
            if ($finduser->userType == 'influencer') {
                $ExistingChannelBelongs = '&inf=1';
            } else {
                $ExistingChannelBelongs = '&inf=0';
            }
            if ($finduser->status == 0 && $finduser->userType == '') {
                UserController::attachAllChannelBelongsVerifiedEmail($sUser->email, $finduser->id);
                $ExistingChannelBelongs = self::fetchChannelByEmail($sUser->email);
            }

            if($finduser->currency=='INR'){
                $from = 'USD';
                $to = 'INR';
            } else{
                $from = 'INR';
                $to = 'USD';
            }
            if($finduser->manager_id==''){
                $Subs = \DB::table('subscriptions')->where('user_id',$finduser->id)->where('status','1')->first();
                $plan_id =(!empty($Subs)) ? $Subs->subscription_plan_id :'1';

                updateRelationshipMgr($plan_id,$finduser->id);
            }
            $amount = currencyConvert($from,$to,'1');
            $saveUser = User::find($finduser->id);
            $saveUser->ops_currency = $from;
            $saveUser->ops_currency_rate = $amount;
            $saveUser->save();

            $reg_status = ($finduser['status'] == '1') ? 1 : 0;
            $user_email = $finduser->email;
            $socialUser = (!empty($finduser)) ? $finduser : NULL;

            $redirectURL = $baseRedirURL . $provider . '?access-token=' . $finduser['access_token']  .'&reg-status=' . $reg_status .'&email=' .$user_email. $ExistingChannelBelongs;
            return redirect($redirectURL);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public static function fetchChannelByEmail($email) {
        $channel = Channel::join('channel_association', 'channels.id', '=', 'channel_association.internal_channel_id')
                        ->where('channels.channel_email', $email)->orWhere('channels.email', $email)->groupBy('channels.channel')->get();
        if (!empty($channel)) {
            $ChannelData = [];
            foreach ($channel as $chnls) {
                $ch['channel'] = $chnls->channel;
                $ch['canonical_name'] = $chnls->canonical_name;
                $ch['channel_name'] = $chnls->channel_name;
                $ch['channel_lang'] = $chnls->channel_lang;
                $ch['promotion_price'] = $chnls->promotion_price;
                $ChannelData[] = $ch;
            }
            $encodedChannel = (!empty($ChannelData)) ? urlencode(json_encode($ChannelData)): '';
            return '&inf=1&channel_data=' . $encodedChannel;
        } else {
            return '&inf=0';
        }
    }

}
