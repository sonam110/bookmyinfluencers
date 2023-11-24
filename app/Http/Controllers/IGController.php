<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IGController extends Controller
{
    public $authData;

    public $auth_table = 'ig_auth_data';

    public function __construct(){

    }

    public function generateIGToken(){

        $ig_atu = "https://api.instagram.com/oauth/access_token";

        $ig_data = [];

        $ig_data['client_id'] = "120580657590387"; //replace with your Instagram app ID

        $ig_data['client_secret'] = "b4eecc2be35491c6e267852eb73ae9f3"; //replace with your Instagram app secret

        $ig_data['grant_type'] = 'authorization_code';

        $ig_data['redirect_uri'] = url('/'). '/callback'; //create this redirect uri in your routes web.php file

        print_r($ig_data['redirect_uri']);
        die;

        $ig_data['code'] = "JSDFRG54TERYGSDGDS4RWEFS"; //this is the code you received in step 1 after app authorization

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $ig_atu);

        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($ig_data));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ig_auth_data = curl_exec($ch);

        curl_close ($ch);

        $ig_auth_data = json_decode($ig_auth_data, true);

        if (!isset($ig_auth_data['error_message'])) {

            $this->authData['access_token'] = $ig_auth_data['access_token'];

            $this->authData['user_id'] = $ig_auth_data['user_id'];


            DB::table($this->auth_table)->insert([
                'access_token'  => $this->authData['access_token'],
                'user_id'       => $this->authData['user_id'],
                'valid_till'    => time(),
                'expires_in'    => 3600,
                'created_at'    => date('Y-m-d H:i:s')
            ]);

        }

    }
    public function callback(Request $request){
        print_r('11');

    }


    public function refreshIGToken($short_access_token){

        $client_secret = "63a0fad8218c422116c7f861fbf683a0"; //replace with your Instagram app secret

        $ig_rtu = 'https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret='.$client_secret.'&access_token='.$short_access_token;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $ig_rtu);
        
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ig_new = curl_exec($ch);

        curl_close ($ch);

        $ig_new = json_decode($ig_new, true);

        if (!isset($ig_new['error'])) {

            $this->authData['access_token'] = $ig_new['access_token'];

            $this->authData['expires_in'] = $ig_new['expires_in'];

            DB::table($this->auth_table)
            ->where('access_token', '<>', '')
            ->update([
                'access_token'  => $ig_new['access_token'],
                'valid_till'    => time(),
                'expires_in'    => $ig_new['expires_in']
            ]);

        }

    }


    public function getMedia(){

        /*check token available and valid*/

        $igData = DB::table($this->auth_table);

        if ($igData->count() > 0) {

            $igDataResult = $igData->first();

            $curTimeStamp = time();

            if (($curTimeStamp-$igDataResult->valid_till) >= $igDataResult->expires_in) {
                
                $this->refreshIGToken($igDataResult->access_token);

            }else{

                $this->authData['access_token'] = $igDataResult->access_token;
                $this->authData['user_id'] = $igDataResult->user_id;

            }

        }else{
            $this->generateIGToken();
        }

        /*check token available and valid*/

        $ig_graph_url = 'https://graph.instagram.com/me/media?fields=id,media_type,media_url,username,timestamp,caption&access_token='.$this->authData['access_token'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $ig_graph_url);
        
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ig_graph_data = curl_exec($ch);

        curl_close ($ch);

        $ig_graph_data = json_decode($ig_graph_data, true);

        $ig_photos = [];

        if (!isset($ig_graph_data['error'])) {

            foreach ($ig_graph_data['data'] as $key => $value) {

                if ($value['media_type'] == 'IMAGE') {
                    $ig_photos[] = $value['media_url'];
                }
                
            }

        }

        //use this if want json response
        //return response()->json($igPhotos);

        return $igPhotos;

    }

    public function igRedirectUri(){
        //write your code here to check the response from oauth redirect uri which you setup in facebook developer console
    }
}
