<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Amirsarhang\Instagram;
use Laravel\Socialite\Facades\Socialite;
class InstagramController extends Controller
{

    public function facebookRedirect()
    {
        return Socialite::driver('facebook')->redirect();
    }
    public function loginWithFacebook()
    {
        try {
    
            $user = Socialite::driver('facebook')->user();
            return $user;
            $isUser = User::where('fb_id', $user->id)->first();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
            
    }

/*----------Insta-------------------*/
    public function instagramLogin()
    {
        try {
        // Go to FB Documentations to see available permissions
        $permissions = [
            'public_profile', 
            'instagram_basic',
            'instagram_manage_insights ',
            'pages_show_list',
            'instagram_manage_comments',
            'instagram_manage_messages',
            'pages_manage_engagement',
            'pages_read_engagement',
            'pages_manage_metadata'
        ];
        
        // Generate Instagram Graph Login URL
        $login = (new Instagram())->getLoginUrl($permissions);
        
        // Redirect To Facebook Login & Select Account Page
        return header("Location: ".$login);

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /*----------callback----------------------*/
    public function instagramCallBack()
    {
        // Generate User Access Token After User Callback To Your Site
        $token =  Instagram::getUserAccessToken();

        $instagram = new Instagram($token);

    // Will return all instagram accounts that connected to your facebook selected pages.
        return $instagram->getConnectedAccountsList(); 
    }
    public function auth1()
    {
        /*$scopes = ['user_profile','user_followers', 'user_friends', 'user_media'];
        $authUrl = 'https://api.instagram.com/oauth/authorize' .
            '?client_id=' . $this->clientId .
            '&redirect_uri=' . urlencode($this->redirectUri) .
            '&scope=' . implode(',', $scopes) .
            '&response_type=code';

        return redirect()->away($authUrl);*/


        $clientId = '1024829825404572'; // Replace with your app's Client ID
        $redirectUri = 'YOUR_REDIRECT_URI'; // Replace with your app's Redirect URI
        $scope = 'user_profile,user_media'; // Replace with desired scope(s)

        $authUrl = "https://api.instagram.com/oauth/authorize?client_id={$clientId}&redirect_uri={$redirectUri}&scope={$scope}&response_type=code";

        return redirect()->away($authUrl);
    }

    public function callback1(Request $request)
    {
        /*$code = $request->query('code');

        $client = new Client();

        $response = $client->post('https://api.instagram.com/oauth/access_token', [
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri,
                'code' => $code,
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        // Use $data to store and process the access token and other information

        //return 'Access token: ' . $data['access_token'];

        $accessToken = $data['access_token'];*/

        $clientId = '1024829825404572'; // Replace with your app's Client ID
        $clientSecret = '64f5a53dc2db5fc76ea90f785c259627'; // Replace with your app's Client Secret
        $redirectUri = 'https://stage-api.bookmyinfluencers.com/instagram/callback'; // Replace with your app's Redirect URI

        $authorizationCode = $request->input('code');

        $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $authorizationCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if ($response->successful()) {
            $accessToken = $response->json()['access_token'];
            // Store or utilize the access token as needed
        } else {
            // Handle the access token retrieval failure
        }
        $client = new Client();
        $profileResponse = $client->get("https://graph.instagram.com/me?fields=id,username&access_token=".$accessToken."");

        $profileData = json_decode($profileResponse->getBody(), true);
       // return $profileData;

        // Access the profile information
        $username = $profileData['username'];
        $userId = $profileData['id'];




    

        $followers = $this->getFollowers($client, $accessToken);
        $following = $this->getFollowing($client, $accessToken);
        $mediaCount = $this->getMediaCount($client, $accessToken);
        
        // Return the data as a response
        return response()->json([
            'followers' => $followers,
            'following' => $following,
            'video_count' => $videoCount,
        ]);

    
       
        
    }

    protected function getFollowers($client, $accessToken)
    {
        $url = "https://graph.instagram.com/me/followers?access_token={$accessToken}";
        $response = $client->get($url);
        $followersData = json_decode($response->getBody(), true);
        
        return count($followersData['data'] ?? []);
    }

    protected function getFollowing($client, $accessToken)
    {
        $url = "https://graph.instagram.com/me/following?access_token={$accessToken}";
        $response = $client->get($url);
        $followingData = json_decode($response->getBody(), true);
        
        return count($followingData['data'] ?? []);
    }

    protected function getMediaCount($client, $accessToken)
    {
        $url = "https://graph.instagram.com/me/media?fields=id&access_token={$accessToken}";
        $response = $client->get($url);
        $mediaData = json_decode($response->getBody(), true);

        return count($mediaData['data'] ?? []);
    }
}
