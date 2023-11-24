<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\User;
use Illuminate\Support\Facades\Auth;

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
        switch ($provider) {
            case 'google':
                $db_column = 'google_id';
                break;
            case 'facebook':
                $db_column = 'facebook_id';
                break;
            default :
                $db_column = null;
        }
        try {
            $user = Socialite::driver($provider)->stateless()->user();
            $finduser = User::where($db_column, $user->id)->first();

            if ($finduser) {
                $userData = Auth::login($finduser);
                return $userData;
            } else {
                $newUser = User::create([
                            'name' => $user->name,
                            'email' => $user->email,
                            $db_column => $user->id,
                            'password' => encrypt('Whoknows@Socialite#0')
                ]);

                $userData = Auth::login($newUser);
                return $userData;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

}
