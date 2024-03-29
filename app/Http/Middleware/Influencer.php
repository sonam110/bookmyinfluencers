<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Influencer {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next) {
        if (!\Auth::check()) {
            return redirect(route('login'));
        }

        if ($request->user()->hasRole('brand')) {
            return response()->json([
                        'success' => false,
                        'message' => 'User not authorized to access this route.',
                        'payload' => [],
                        'code' => '401'
            ]);
        }

        return $next($request);
    }

}
