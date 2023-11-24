<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\OauthAccessTokens;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class isActiveToken {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next) {

        $user = Auth::user();
        $userSerialize = serialize($user);
        $userUnserializeArray = (array) unserialize($userSerialize);

        $arrayKeys = array_keys($userUnserializeArray);
        foreach ($arrayKeys as $value) {

            if (strpos($value, 'accessToken') !== false) {

                $userAccessTokenArray = (array) $userUnserializeArray[$value];
                $arrayAccessKeys = array_keys($userAccessTokenArray);
                foreach ($arrayAccessKeys as $arrayAccessValue) {

                    if (strpos($arrayAccessValue, 'original') !== false) {

                        $userTokenId = $userAccessTokenArray[$arrayAccessValue]['id'];
                        $checkToken = OauthAccessTokens::where([
                                    ['id', '=', $userTokenId],
                                    ['expires_at', '>', Carbon::now()]
                                ])->first();

                        if (!$checkToken) {
                            return response()->json([
                                        'success' => false,
                                        'message' => 'Token time has expired. Please log in again.',
                                        'payload' => [],
                                        'code' => '301'
                            ]);
                        }
                    }
                }
            }
        }

        return $next($request);
    }

}
