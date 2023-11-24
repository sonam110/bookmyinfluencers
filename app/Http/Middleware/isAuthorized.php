<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class isAuthorized {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next) {
        $user = getUser();
        if (isset($user->status) && $user->status != 1) {
            return response()->json([
                        'success' => false,
                        'message' => 'User not authenticate.',
                        'payload' => [],
                        'code' => '401'
            ]);
        }
        return $next($request);
    }

}
